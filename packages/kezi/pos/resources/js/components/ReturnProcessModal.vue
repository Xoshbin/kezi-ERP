<template>
    <div v-if="visible" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="p-6 border-b dark:border-gray-800 flex items-center justify-between bg-white dark:bg-gray-900">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-rose-50 dark:bg-rose-500/10 rounded-2xl flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Return Items</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Transaction #{{ selectedOrder?.order_number }}</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="w-10 h-10 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center justify-center text-gray-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-8 bg-gray-50/50 dark:bg-gray-950/20">
                <!-- Order Summary -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800/50 p-4 rounded-2xl border dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Customer</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedOrder?.customer?.name || 'Walk-in Customer' }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800/50 p-4 rounded-2xl border dark:border-gray-700">
                        <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Total Paid</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatMoney(selectedOrder?.total_amount) }}</p>
                    </div>
                </div>

                <!-- Items to Return -->
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4 flex items-center gap-2">
                        Select Items
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-md text-[10px]">{{ returnLines.length }} Products</span>
                    </h3>

                    <div class="space-y-3">
                        <div v-for="(line, index) in returnLines" :key="index"
                            class="bg-white dark:bg-gray-800 p-4 rounded-3xl border-2 transition-all"
                            :class="line.quantity_returned > 0 ? 'border-primary-500 shadow-lg shadow-primary-500/5' : 'border-gray-100 dark:border-gray-700'"
                        >
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-700 rounded-xl flex items-center justify-center text-[10px] font-black text-gray-400 shrink-0">
                                    {{ line.product_sku || 'IMG' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ line.product_name }}</h4>
                                    <p class="text-xs text-gray-500">{{ formatMoney(line.unit_price) }} per unit</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold text-gray-400">Purchased: {{ line.quantity_available }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                <div class="flex items-center gap-3">
                                    <button @click="updateLineQty(index, -1)" class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-750 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition-colors">-</button>
                                    <span class="w-8 text-center font-black text-lg">{{ line.quantity_returned }}</span>
                                    <button @click="updateLineQty(index, 1)" class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-750 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition-colors">+</button>
                                </div>

                                <div v-if="line.quantity_returned > 0" class="flex-1 flex items-center gap-4 animate-in slide-in-from-left-4 duration-300">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" v-model="line.restock" class="w-5 h-5 rounded-lg border-2 border-gray-200 text-primary-600 focus:ring-primary-500/20">
                                        <span class="text-xs font-bold text-gray-500 group-hover:text-gray-700 transition-colors">Restock</span>
                                    </label>

                                    <select v-model="line.item_condition" class="flex-1 bg-gray-50 dark:bg-gray-750 border-none rounded-xl text-xs font-bold p-2 outline-none">
                                        <option value="new">New Condition</option>
                                        <option value="opened">Opened</option>
                                        <option value="damaged">Damaged</option>
                                        <option value="defective">Defective</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Refund Options -->
                <div v-if="hasItemsToReturn" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Refund Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest pl-1">Reason</label>
                                <select v-model="returnReason" class="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl p-4 text-sm font-bold outline-none focus:border-primary-500 transition-all">
                                    <option value="customer_changed_mind">Customer Changed Mind</option>
                                    <option value="wrong_item">Wrong Item / Size</option>
                                    <option value="damaged_defective">Damaged / Defective</option>
                                    <option value="not_as_described">Not as Described</option>
                                    <option value="other">Other Reason</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest pl-1">Method</label>
                                <select v-model="refundMethod" class="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl p-4 text-sm font-bold outline-none focus:border-primary-500 transition-all">
                                    <option value="cash">Cash Refund</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="store_credit">Store Credit</option>
                                    <option value="original_method">Original Method ({{ selectedOrder?.payment_method }})</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest pl-1">Internal Notes</label>
                        <textarea v-model="returnNotes" placeholder="Any additional notes for this return..." rows="3" class="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-2xl p-4 text-sm outline-none focus:border-primary-500 transition-all"></textarea>
                    </div>
                </div>

                <!-- Manager Pin Prompt Banner (shown when requires approval) -->
                <div v-if="requiresManagerApproval && pendingReturnId" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-500/30 p-4 rounded-2xl flex items-center justify-between gap-4 animate-in slide-in-from-bottom-2 duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-500/20 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-amber-800 dark:text-amber-300">Manager Approval Required</p>
                            <p class="text-xs text-amber-600 dark:text-amber-400">Return exceeds policy threshold. Enter manager PIN to continue.</p>
                        </div>
                    </div>
                    <button
                        @click="showPinModal = true"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black transition-all active:scale-95 shadow-lg shadow-amber-500/30 whitespace-nowrap"
                    >
                        Enter PIN
                    </button>
                </div>

                <!-- Error Alert -->
                <div v-if="error" class="bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 p-4 rounded-2xl text-sm font-bold flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    {{ error }}
                </div>
            </div>

            <!-- Footer / Totals -->
            <div class="p-6 border-t dark:border-gray-800 bg-white dark:bg-gray-900 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <div class="flex justify-between items-end mb-6">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs text-gray-500 font-bold">
                            <span>Refund Total</span>
                            <span class="font-black">{{ formatMoney(refundAmount) }}</span>
                        </div>
                        <div v-if="restockingFee > 0" class="flex items-center gap-2 text-xs text-rose-500 font-bold">
                            <span>Restocking Fee</span>
                            <span class="font-black">-{{ formatMoney(restockingFee) }}</span>
                        </div>
                        <div class="text-2xl font-black text-gray-900 dark:text-white tracking-tighter">
                            {{ formatMoney(netRefund) }}
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="$emit('close')" class="px-6 py-4 rounded-2xl font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            Cancel
                        </button>
                        <button
                            @click="handleProcess"
                            :disabled="!hasItemsToReturn || processing"
                            class="px-10 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-2xl font-black shadow-xl disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 active:scale-95 transition-all flex items-center gap-3"
                        >
                            <span v-if="processing" class="w-5 h-5 border-2 border-gray-400 border-t-white dark:border-gray-300 dark:border-t-gray-900 rounded-full animate-spin"></span>
                            {{ processing ? 'Processing...' : 'Complete Return' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6a — Manager PIN Modal (rendered outside the modal so it appears above it) -->
    <ManagerPinModal
        v-if="showPinModal"
        :visible="showPinModal"
        :return-id="pendingReturnId"
        @cancel="showPinModal = false"
        @approved="handlePinApproved"
    />
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useSessionStore } from '../stores/session';
import { useReturnProcess } from '../composables/useReturnProcess';
import ManagerPinModal from './ManagerPinModal.vue';

const props = defineProps({
    visible: Boolean,
    orderData: Object,
});

const emit = defineEmits(['close', 'completed']);

const sessionStore = useSessionStore();
const {
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
} = useReturnProcess();

// 6a — PIN modal state
const showPinModal = ref(false);

const hasItemsToReturn = computed(() => returnLines.value.some(l => l.quantity_returned > 0));

const updateLineQty = (index, delta) => {
    const line = returnLines.value[index];
    const newVal = line.quantity_returned + delta;
    if (newVal >= 0 && newVal <= line.quantity_available) {
        line.quantity_returned = newVal;
    }
};

const handleProcess = async () => {
    try {
        const result = await processReturnRequest();
        if (result && result._requiresApproval) {
            // Don't emit completed yet — wait for manager PIN
            return;
        }
        emit('completed', result);
    } catch {
        // Error handled by composable state
    }
};

const handlePinApproved = async (pinResult) => {
    showPinModal.value = false;
    // After PIN approval, the return is now in 'approved' state — process it
    try {
        const result = await handleManagerApproved(pinResult);
        emit('completed', result);
    } catch {
        // Error shown in composable
    }
};

const formatMoney = (amount) => {
    const val = Number(amount) / sessionStore.decimalFactor;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: sessionStore.currencyCode,
        minimumFractionDigits: sessionStore.decimalPlaces,
    }).format(val);
};

watch(() => props.orderData, (newVal) => {
    if (newVal) {
        initializeReturn(newVal);
    }
}, { immediate: true });
</script>
