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
    const returnReason = ref('');
    const returnNotes = ref('');
    const refundMethod = ref('cash');
    const processing = ref(false);
    const error = ref(null);
    
    // Calculate totals
    const refundAmount = computed(() => {
        return returnLines.value.reduce((sum, line) => {
            return sum + (line.quantity_returned * line.unit_price);
        }, 0);
    });
    
    const restockingFee = computed(() => {
        const policy = sessionStore.currentSession?.profile?.return_policy || {};
        const feePercentage = policy.restocking_fee_percentage || 0;
        return Math.round((refundAmount.value * feePercentage) / 100);
    });
    
    const netRefund = computed(() => {
        return refundAmount.value - restockingFee.value;
    });
    
    // Initialize return from order
    const initializeReturn = (order) => {
        selectedOrder.value = order;
        returnLines.value = order.lines.map(line => ({
            original_order_line_id: line.id,
            product_id: line.product_id,
            product_name: line.product_name,
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
    };
    
    // Submit return (Store + Submit in one go for common case)
    const processReturnRequest = async () => {
        try {
            processing.value = true;
            error.value = null;
            
            const payload = {
                uuid: self.crypto?.randomUUID() || Math.random().toString(36).substring(7),
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
                        unit_price: l.unit_price,
                        refund_amount: l.quantity_returned * l.unit_price,
                        restock: l.restock,
                        item_condition: l.item_condition,
                        restocking_fee_line: Math.round((l.quantity_returned * l.unit_price * (sessionStore.currentSession?.profile?.return_policy?.restocking_fee_percentage || 0)) / 100)
                    })),
            };

            if (!connectivity.isOnline) {
                // Offline support
                const returnId = await db.transaction('rw', db.returns, db.return_lines, async () => {
                    const id = await db.returns.add({
                        ...payload,
                        lines: undefined, // Don't store lines in main table if using separate table
                        sync_status: 'pending',
                        status: 'draft'
                    });
                    
                    const linesWithId = payload.lines.map(l => ({ ...l, return_id: id }));
                    await db.return_lines.bulkAdd(linesWithId);
                    return id;
                });
                
                return { id: returnId, return_number: 'PENDING', status: 'draft' };
            }
            
            // 1. Store the return on server
            const returnData = await syncService.createReturn(payload);
            
            // 2. Submit the return
            const submittedReturn = await syncService.submitReturn(returnData.id);
            
            // 3. If approved automatically, try to process
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
    
    const requestManagerApproval = async (returnId, managerPin) => {
        // In the backend we don't have a specific request-approval with PIN yet,
        // usually manager does it via Filament. But for the terminal we might need it.
        // For now, let's assume manager approves in admin panel or we implement PIN check later.
        // Actually, the plan mentions a PIN modal. We'll need a backend endpoint for this.
        // For now, let's stick to the basic flow.
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
        initializeReturn,
        processReturnRequest,
    };
}
