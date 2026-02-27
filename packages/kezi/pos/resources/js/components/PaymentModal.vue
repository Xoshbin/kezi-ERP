<template>
    <Teleport to="body">
        <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xl transition-all duration-500">
            <div class="bg-white dark:bg-gray-900 w-full max-w-4xl rounded-[2.5rem] shadow-2xl border border-white/20 dark:border-gray-800 overflow-hidden transform transition-all scale-100 opacity-100 flex flex-col md:flex-row" style="max-height: 92vh;">

                <!-- Left Side: Order Summary -->
                <div class="w-full md:w-[38%] bg-gray-50 dark:bg-gray-800/50 p-8 border-r dark:border-gray-800 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="bg-primary-100 text-primary-700 px-3 py-0.5 rounded-full text-[10px] uppercase tracking-wider font-black">
                            {{ cart.totalQuantity }} Items
                        </span>
                        Order Summary
                    </h3>

                    <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                        <div v-for="item in cart.items" :key="item.id" class="flex justify-between items-start text-sm py-1">
                            <div class="flex-1 pr-4">
                                <span class="font-semibold text-gray-800 dark:text-gray-200 block leading-tight">{{ item.name }}</span>
                                <span class="text-[11px] text-gray-500">{{ item.quantity }} x {{ formatMoney(item.unit_price) }}</span>
                            </div>
                            <span class="font-semibold text-gray-900 dark:text-gray-100 shrink-0 text-right">{{ formatMoney(item.unit_price * item.quantity) }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t dark:border-gray-700 space-y-3">
                        <div class="flex justify-between text-gray-500 text-sm">
                            <span>Subtotal</span>
                            <span class="font-medium">{{ formatMoney(cart.subtotal) }}</span>
                        </div>
                        <div v-if="cart.totalDiscount > 0" class="flex justify-between text-rose-500 text-sm">
                            <span>Discount</span>
                            <span class="font-medium">-{{ formatMoney(cart.totalDiscount) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500 text-sm">
                            <span>Tax</span>
                            <span class="font-medium">{{ formatMoney(cart.tax) }}</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-4 border-t dark:border-gray-700 gap-2">
                            <span class="text-lg font-bold text-gray-900 dark:text-white shrink-0">Total</span>
                            <span class="text-2xl font-black text-primary-600 tracking-tighter text-right">{{ formatMoney(cart.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Payment -->
                <div class="flex-1 p-8 flex flex-col relative overflow-y-auto">
                    <!-- Close Button -->
                    <button @click="closeModal" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>

                    <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-1">Payment</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Add one or more payment methods to complete the sale.</p>

                    <!-- Remaining Balance indicator -->
                    <div :class="[
                        'mb-5 p-4 rounded-2xl border flex items-center justify-between transition-all',
                        remainingBalance > 0
                            ? 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-700 dark:text-amber-400'
                            : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                    ]">
                        <span class="font-bold text-sm">{{ remainingBalance > 0 ? 'Remaining Balance' : 'Fully Paid' }}</span>
                        <span class="font-black text-xl">{{ formatMoney(Math.abs(remainingBalance)) }}</span>
                    </div>

                    <!-- Payment Lines -->
                    <div class="space-y-4 mb-4 flex-1">
                        <div
                            v-for="(line, index) in paymentLines"
                            :key="index"
                            class="rounded-2xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/40"
                        >
                            <!-- Line Header -->
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold uppercase tracking-widest text-gray-400">
                                    Payment {{ paymentLines.length > 1 ? index + 1 : '' }}
                                </span>
                                <button
                                    v-if="paymentLines.length > 1"
                                    @click="removeLine(index)"
                                    class="w-6 h-6 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-500 hover:bg-rose-200 dark:hover:bg-rose-800/40 flex items-center justify-center transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Method selector -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <button
                                    @click="line.method = 'cash'"
                                    :class="[
                                        'py-3 rounded-xl border-2 flex items-center justify-center gap-2 text-sm font-bold transition-all',
                                        line.method === 'cash'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 shadow-sm'
                                            : 'border-gray-200 dark:border-gray-700 text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                                    ]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    Cash
                                </button>
                                <button
                                    @click="line.method = 'credit_card'"
                                    :class="[
                                        'py-3 rounded-xl border-2 flex items-center justify-center gap-2 text-sm font-bold transition-all',
                                        line.method === 'credit_card'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 shadow-sm'
                                            : 'border-gray-200 dark:border-gray-700 text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                                    ]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    Card
                                </button>
                            </div>

                            <!-- Amount input -->
                            <div class="mb-2">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1 block ml-1">Amount</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-primary-600 font-bold text-lg">{{ currencySymbol }}</span>
                                    <input
                                        v-model="line.amountInput"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-full bg-white dark:bg-gray-900 border-2 border-transparent focus:border-primary-500 rounded-2xl py-3 pl-12 pr-4 outline-none transition-all font-black text-xl text-gray-900 dark:text-white"
                                    >
                                </div>
                            </div>

                            <!-- Cash: amount tendered + change -->
                            <template v-if="line.method === 'cash'">
                                <div class="mb-2">
                                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1 block ml-1">Tendered</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-4 flex items-center text-gray-400 font-bold text-lg">{{ currencySymbol }}</span>
                                        <input
                                            v-model="line.amountTenderedInput"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            class="w-full bg-white dark:bg-gray-900 border-2 border-transparent focus:border-primary-400 rounded-2xl py-3 pl-12 pr-4 outline-none transition-all font-bold text-lg text-gray-700 dark:text-gray-300"
                                        >
                                    </div>
                                </div>
                                <div v-if="lineChangeDue(line) > 0" class="flex items-center justify-between bg-emerald-50 dark:bg-emerald-500/10 rounded-xl px-4 py-2 text-emerald-700 dark:text-emerald-400 text-sm">
                                    <span class="font-semibold">Change Due</span>
                                    <span class="font-black">{{ formatMoney(lineChangeDue(line)) }}</span>
                                </div>

                                <!-- Quick amounts -->
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <button @click="setLineTendered(line, lineAmountMinor(line))" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Exact</button>
                                    <button v-for="amt in quickAmountsFor(line)" :key="amt" @click="setLineTendered(line, amt)" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        {{ formatMoney(amt) }}
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Add Payment Button -->
                    <button
                        v-if="remainingBalance > 0"
                        @click="addLine"
                        class="w-full py-3 mb-4 rounded-2xl border-2 border-dashed border-primary-300 dark:border-primary-700 text-primary-600 dark:text-primary-400 font-bold text-sm hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all flex items-center justify-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Another Payment Method
                    </button>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <button @click="closeModal" class="py-4 rounded-3xl font-black text-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button
                            @click="completeSale"
                            :disabled="!isValidPayment"
                            class="py-4 rounded-3xl font-black text-lg bg-primary-600 !text-gray-900 shadow-xl shadow-primary-500/30 hover:bg-primary-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-tight"
                        >
                            Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useCartStore } from '../stores/cart';
import { useSessionStore } from '../stores/session';

const props = defineProps({
    visible: Boolean,
    currencyCode: { type: String, default: 'USD' }
});

const emit = defineEmits(['close', 'payment-complete']);

const sessionStore = useSessionStore();
const cart = useCartStore();

/** @returns {{ method: string, amountInput: string, amountTenderedInput: string }} */
const newLine = (amountMinor = 0) => ({
    method: 'cash',
    amountInput: amountMinor > 0 ? (amountMinor / sessionStore.decimalFactor).toFixed(sessionStore.decimalPlaces) : '',
    amountTenderedInput: amountMinor > 0 ? (amountMinor / sessionStore.decimalFactor).toFixed(sessionStore.decimalPlaces) : '',
});

const paymentLines = ref([newLine()]);

// Reset when modal opens
watch(() => props.visible, (newVal) => {
    if (newVal) {
        paymentLines.value = [newLine(cart.total)];
    }
});

const closeModal = () => emit('close');

const currencySymbol = computed(() => {
    return (0).toLocaleString('en-US', { style: 'currency', currency: props.currencyCode, minimumFractionDigits: 0, maximumFractionDigits: 0 }).replace(/\d/g, '').trim();
});

const lineAmountMinor = (line) => {
    if (!line.amountInput) return 0;
    return Math.round(parseFloat(line.amountInput) * sessionStore.decimalFactor);
};

const lineTenderedMinor = (line) => {
    if (!line.amountTenderedInput) return 0;
    return Math.round(parseFloat(line.amountTenderedInput) * sessionStore.decimalFactor);
};

const lineChangeDue = (line) => {
    if (line.method !== 'cash') return 0;
    return Math.max(0, lineTenderedMinor(line) - lineAmountMinor(line));
};

const totalPaidMinor = computed(() =>
    paymentLines.value.reduce((sum, l) => sum + lineAmountMinor(l), 0)
);

const remainingBalance = computed(() => cart.total - totalPaidMinor.value);

const isValidPayment = computed(() => {
    if (!props.visible) return false;
    if (remainingBalance.value > 0) return false;
    // Cash lines must have amount_tendered >= amount
    return paymentLines.value.every(line => {
        if (line.method === 'cash') {
            return lineTenderedMinor(line) >= lineAmountMinor(line);
        }
        return lineAmountMinor(line) > 0;
    });
});

const addLine = () => {
    const remaining = remainingBalance.value;
    paymentLines.value.push(newLine(remaining > 0 ? remaining : 0));
};

const removeLine = (index) => {
    paymentLines.value.splice(index, 1);
};

const setLineTendered = (line, amountMinor) => {
    line.amountTenderedInput = (amountMinor / sessionStore.decimalFactor).toFixed(sessionStore.decimalPlaces);
};

const quickAmountsFor = (line) => {
    const lineAmount = lineAmountMinor(line);
    const amounts = [];
    const nextRound = Math.ceil(lineAmount / sessionStore.decimalFactor) * sessionStore.decimalFactor;
    if (nextRound > lineAmount) amounts.push(nextRound);
    [100, 200, 500, 1000, 2000, 5000, 10000, 25000, 50000].forEach(noteValue => {
        const noteMinor = noteValue * sessionStore.decimalFactor;
        if (noteMinor > lineAmount && !amounts.includes(noteMinor) && amounts.length < 4) {
            amounts.push(noteMinor);
        }
    });
    return amounts.sort((a, b) => a - b);
};

const formatMoney = (amount) => {
    if (amount === undefined || amount === null) return '0.00';
    const val = Number(amount) / sessionStore.decimalFactor;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.currencyCode,
        minimumFractionDigits: sessionStore.decimalPlaces,
        maximumFractionDigits: sessionStore.decimalPlaces,
    }).format(val);
};

const completeSale = () => {
    if (!isValidPayment.value) return;

    const payments = paymentLines.value.map(line => ({
        method: line.method,
        amount: lineAmountMinor(line),
        amount_tendered: line.method === 'cash' ? lineTenderedMinor(line) : lineAmountMinor(line),
        change_given: lineChangeDue(line),
    }));

    emit('payment-complete', { payments });
};
</script>
