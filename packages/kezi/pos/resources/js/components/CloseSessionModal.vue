<template>
    <Teleport to="body">
        <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xl transition-all duration-500">
            <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl border border-white/20 dark:border-gray-800 overflow-hidden transform transition-all scale-100 opacity-100">
                <!-- Header -->
                <div class="p-8 pb-4 text-center">
                    <div class="w-20 h-20 bg-rose-100 dark:bg-rose-900/30 rounded-3xl flex items-center justify-center text-rose-600 mx-auto mb-6 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Close Session</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 font-medium">End your shift and reconcile the cash drawer</p>
                </div>

                <div class="p-8 pt-4 space-y-6">
                    <!-- Session Summary Card -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-[2rem] p-6 border border-gray-100 dark:border-gray-700/50">
                        <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Session ID</label>
                                <p class="font-black text-gray-900 dark:text-white">#{{ sessionStore.sessionId }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Profile</label>
                                <p class="font-black text-gray-900 dark:text-white truncate">{{ sessionStore.profileName }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Opened At</label>
                                <p class="font-bold text-sm text-gray-700 dark:text-gray-300">{{ formatDateTime(sessionStore.currentSession?.opened_at) }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Orders</label>
                                <p class="font-black text-gray-900 dark:text-white">{{ sessionStore.currentSession?.order_count || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Reconciliation -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-xs font-bold uppercase tracking-widest text-gray-400">Cash Reconciliation</label>
                            <div class="text-[10px] bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 px-2 py-0.5 rounded-full font-bold">
                                Opening: {{ formatMoney(sessionStore.currentSession?.opening_cash_minor) }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-2xl border-2 border-transparent">
                                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1 block">Expected Cash</label>
                                    <p class="text-xl font-black text-gray-900 dark:text-white">{{ formatMoney(expectedCash) }}</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="relative group">
                                    <span class="absolute top-4 left-4 text-primary-600 font-bold opacity-50">{{ sessionStore.currencyCode }}</span>
                                    <input 
                                        v-model="form.actualCash"
                                        type="number" 
                                        :step="1 / sessionStore.decimalFactor"
                                        placeholder="0.00"
                                        class="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 focus:border-primary-500 rounded-2xl py-4 pl-14 pr-4 outline-none transition-all font-black text-xl text-gray-900 dark:text-white shadow-sm"
                                    >
                                    <label class="absolute -top-2.5 left-4 bg-white dark:bg-gray-800 px-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">Actual Cash</label>
                                </div>
                            </div>
                        </div>

                        <!-- Discrepancy Signal -->
                        <div v-if="form.actualCash !== ''" class="px-4 py-3 rounded-2xl text-sm font-bold flex justify-between items-center transition-all animate-in fade-in slide-in-from-top-2" :class="discrepancyClass">
                            <span>Discrepancy</span>
                            <span>{{ discrepancyFormatted }}</span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <div class="relative">
                            <textarea 
                                v-model="form.notes"
                                rows="2"
                                placeholder="Optional shift notes..."
                                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-primary-500 rounded-2xl py-3 px-4 outline-none transition-all font-medium text-sm text-gray-700 dark:text-gray-300 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Error Display -->
                    <div v-if="sessionStore.error" class="bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 px-4 py-3 rounded-2xl text-sm font-medium flex gap-3 items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        {{ sessionStore.error }}
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        <button 
                            @click="$emit('close')"
                            class="flex-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 py-4 rounded-3xl font-bold transition-all active:scale-[0.98]"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="handleCloseSession"
                            :disabled="sessionStore.loading || form.actualCash === ''"
                            class="flex-[2] bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:scale-[1.02] disabled:opacity-50 disabled:scale-100 disabled:cursor-not-allowed py-4 rounded-3xl font-black text-lg shadow-xl transition-all active:scale-[0.98] flex items-center justify-center gap-3"
                        >
                            <span v-if="sessionStore.loading" class="animate-spin rounded-full h-5 w-5 border-2 border-current/20 border-t-current"></span>
                            <span v-else>Close Session</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useSessionStore } from '../stores/session';

const props = defineProps({
    visible: Boolean,
    currencyCode: {
        type: String,
        default: 'USD'
    }
});

const emit = defineEmits(['close', 'session-closed']);

const sessionStore = useSessionStore();

const form = reactive({
    actualCash: '',
    notes: ''
});

// For now, expected cash = Opening Cash
// The task says: "Expected cash = Opening Cash + Total Cash Revenue (display-only)"
// "For now, since we don't track payment methods on the backend yet, just show the opening cash"
const expectedCash = computed(() => {
    return sessionStore.currentSession?.opening_cash_minor || 0;
});

const actualCashMinor = computed(() => {
    return Math.round(parseFloat(form.actualCash || 0) * sessionStore.decimalFactor);
});

const discrepancy = computed(() => {
    return actualCashMinor.value - expectedCash.value;
});

const discrepancyClass = computed(() => {
    const diff = Math.abs(discrepancy.value);
    if (diff === 0) return 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
    if (diff < 1000) return 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400';
    return 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400';
});

const discrepancyFormatted = computed(() => {
    const prefix = discrepancy.value > 0 ? '+' : '';
    return prefix + formatMoney(discrepancy.value);
});

const handleCloseSession = async () => {
    if (form.actualCash === '') return;
    
    try {
        const summary = await sessionStore.closeSession(actualCashMinor.value);
        emit('session-closed', summary);
    } catch (e) {
        // Error handled in store
    }
};

const formatMoney = (amount) => {
    if (amount === undefined || amount === null) return '0.00';
    const val = Number(amount) / sessionStore.decimalFactor;
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: sessionStore.currencyCode,
        minimumFractionDigits: sessionStore.decimalPlaces,
        maximumFractionDigits: sessionStore.decimalPlaces
    }).format(val);
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
