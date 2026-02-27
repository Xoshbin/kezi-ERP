import { ref, computed } from 'vue';
import { useSessionStore } from '../stores/session';
import { useConnectivityStore } from '../stores/connectivity';
import * as syncService from '../services/sync-service';
import { db } from '../db/pos-db';

export function useReturnProcess() {
    const sessionStore = useSessionStore();
    const connectivity = useConnectivityStore();

    const selectedOrder = ref(null);
    const returnLines = ref([]);
    const returnReason = ref('customer_changed_mind');
    const returnNotes = ref('');
    const refundMethod = ref('cash');
    const processing = ref(false);
    const error = ref(null);

    // 6a — Manager approval state
    const requiresManagerApproval = ref(false);
    const pendingReturnId = ref(null);

    // ---------- Computed totals ----------

    const refundAmount = computed(() =>
        returnLines.value.reduce((sum, line) => sum + (line.quantity_returned * line.unit_price), 0)
    );

    const restockingFee = computed(() => {
        const policy = sessionStore.currentSession?.profile?.return_policy || {};
        const feePercentage = policy.restocking_fee_percentage || 0;
        return Math.round((refundAmount.value * feePercentage) / 100);
    });

    const netRefund = computed(() => refundAmount.value - restockingFee.value);

    // ---------- Initialise return from order ----------

    const initializeReturn = (order) => {
        selectedOrder.value = order;
        returnLines.value = order.lines.map(line => ({
            original_order_line_id: line.id,
            product_id: line.product_id,
            product_name: line.product_name || line.product?.name,
            product_sku: line.product_sku || line.product?.sku,
            quantity_available: line.quantity,
            quantity_returned: 0,
            unit_price: line.unit_price,
            restock: true,
            item_condition: 'new',
            return_reason_line: null,
            metadata: line.metadata,
        }));
        returnReason.value = 'customer_changed_mind';
        returnNotes.value = '';
        refundMethod.value = 'cash';
        error.value = null;
        requiresManagerApproval.value = false;
        pendingReturnId.value = null;
    };

    // ---------- Process return ----------

    const processReturnRequest = async () => {
        try {
            processing.value = true;
            error.value = null;
            requiresManagerApproval.value = false;

            const payload = {
                uuid: (typeof crypto !== 'undefined' && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                        const r = Math.random() * 16 | 0;
                        const v = c === 'x' ? r : (r & 0x3 | 0x8);
                        return v.toString(16);
                    }),
                pos_session_id: sessionStore.sessionId,
                original_order_id: selectedOrder.value.id,
                currency_id: selectedOrder.value.currency_id || sessionStore.currentSession?.currency_id,
                return_date: new Date().toISOString(),
                return_reason: returnReason.value,
                return_notes: returnNotes.value,
                refund_method: refundMethod.value,
                lines: returnLines.value
                    .filter(l => l.quantity_returned > 0)
                    .map(l => ({
                        product_id: l.product_id,
                        original_order_line_id: l.original_order_line_id,
                        quantity_returned: l.quantity_returned,
                        quantity_available: l.quantity_available,
                        unit_price: l.unit_price,
                        refund_amount: l.quantity_returned * l.unit_price,
                        restock: l.restock,
                        item_condition: l.item_condition,
                        return_reason_line: l.return_reason_line,
                        restocking_fee_line: Math.round(
                            (l.quantity_returned * l.unit_price *
                                (sessionStore.currentSession?.profile?.return_policy?.restocking_fee_percentage || 0)) / 100
                        ),
                        metadata: l.metadata || [],
                    })),
            };

            if (!connectivity.isOnline) {
                // Offline support — save to IndexedDB
                const returnId = await db.transaction('rw', db.returns, db.return_lines, async () => {
                    const id = await db.returns.add({
                        ...payload,
                        lines: undefined,
                        sync_status: 'pending',
                        status: 'draft',
                    });
                    const linesWithId = payload.lines.map(l => ({ ...l, return_id: id }));
                    await db.return_lines.bulkAdd(linesWithId);
                    return id;
                });
                return { id: returnId, return_number: 'PENDING', status: 'draft' };
            }

            // 1. Create the return on the server
            const returnData = await syncService.createReturn(payload);

            // 2. Submit it (moves to pending_approval or approved depending on policy)
            const submittedReturn = await syncService.submitReturn(returnData.id);

            // 3a. Requires manager approval — surface the PIN modal instead of processing
            if (submittedReturn.status === 'pending_approval') {
                requiresManagerApproval.value = true;
                pendingReturnId.value = submittedReturn.id;
                return { _requiresApproval: true, id: submittedReturn.id };
            }

            // 3b. Auto-approved — process immediately
            if (submittedReturn.status === 'approved') {
                return await syncService.processReturn(submittedReturn.id);
            }

            return submittedReturn;
        } catch (e) {
            console.error('Return process failed', e);
            error.value = e.response?.data?.message || 'Failed to process return. Please try again.';
            throw e;
        } finally {
            processing.value = false;
        }
    };

    /**
     * 6a — Called after the manager PIN modal emits 'approved'.
     * The backend has already approved the return; we just need to process it.
     */
    const handleManagerApproved = async (pinResult) => {
        try {
            processing.value = true;
            error.value = null;
            requiresManagerApproval.value = false;

            const returnId = pendingReturnId.value;
            pendingReturnId.value = null;

            const processed = await syncService.processReturn(returnId);
            return processed;
        } catch (e) {
            console.error('Processing after manager approval failed', e);
            error.value = e.response?.data?.message || 'Failed to process return after approval.';
            throw e;
        } finally {
            processing.value = false;
        }
    };

    return {
        selectedOrder,
        returnLines,
        returnReason,
        returnNotes,
        refundMethod,
        processing,
        error,
        refundAmount,
        restockingFee,
        netRefund,
        requiresManagerApproval,
        pendingReturnId,
        initializeReturn,
        processReturnRequest,
        handleManagerApproved,
    };
}
