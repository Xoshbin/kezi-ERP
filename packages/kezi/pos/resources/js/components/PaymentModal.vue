<template>
    <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xl transition-all duration-500">
        <div class="bg-white dark:bg-gray-900 w-full max-w-4xl h-[90vh] rounded-[2.5rem] shadow-2xl border border-white/20 dark:border-gray-800 overflow-hidden transform transition-all scale-100 opacity-100 flex flex-col md:flex-row">
            
            <!-- Left Side: Order Summary -->
            <div class="w-full md:w-1/3 bg-gray-50 dark:bg-gray-800/50 p-8 border-r dark:border-gray-800 flex flex-col">
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-xs">
                        {{ cart.totalQuantity }} Items
                    </span>
                    Order Summary
                </h3>
                
                <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                    <div v-for="item in cart.items" :key="item.id" class="flex justify-between items-start text-sm">
                        <div class="flex-1">
                            <span class="font-bold text-gray-800 dark:text-gray-200 block">{{ item.name }}</span>
                            <span class="text-xs text-gray-500">{{ item.quantity }} x {{ formatMoney(item.unit_price) }}</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatMoney(item.unit_price * item.quantity) }}</span>
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
                    <div class="flex justify-between items-end pt-4 border-t dark:border-gray-700">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">Total</span>
                        <span class="text-4xl font-black text-primary-600">{{ formatMoney(cart.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Payment -->
            <div class="flex-1 p-8 flex flex-col relative">
                <!-- Close Button (Top Right) -->
                 <button @click="closeModal" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Payment</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-8">Select a payment method to complete the sale.</p>

                <!-- Payment Methods -->
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <button 
                        @click="paymentMethod = 'cash'"
                        :class="[
                            'p-6 rounded-3xl border-2 flex flex-col items-center gap-3 transition-all',
                            paymentMethod === 'cash' 
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 font-bold shadow-lg shadow-primary-500/10'
                                : 'border-dashed border-gray-200 dark:border-gray-700 text-gray-400 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <span>Cash</span>
                    </button>
                    <button 
                        @click="paymentMethod = 'card'"
                        :class="[
                            'p-6 rounded-3xl border-2 flex flex-col items-center gap-3 transition-all',
                            paymentMethod === 'card' 
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 font-bold shadow-lg shadow-primary-500/10'
                                : 'border-dashed border-gray-200 dark:border-gray-700 text-gray-400 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                        <span>Card</span>
                    </button>
                </div>

                <!-- Cash Payment Details -->
                <div v-if="paymentMethod === 'cash'" class="space-y-6 flex-1">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-gray-400 ml-1">Amount Tendered</label>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-5 flex items-center text-primary-600 font-bold text-2xl">{{ currencySymbol }}</span>
                            <input 
                                v-model="amountTenderedInput"
                                type="number" 
                                step="0.01"
                                placeholder="0.00"
                                class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-primary-500 rounded-3xl py-6 pl-12 pr-5 outline-none transition-all font-black text-4xl text-gray-900 dark:text-white"
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <!-- Precise -->
                        <button @click="setTendered(cart.total)" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-xl font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">Exact</button>
                        <!-- Next dollar -->
                         <button v-for="amt in quickAmounts" :key="amt" @click="setTendered(amt)" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-xl font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            {{ formatMoney(amt) }}
                         </button>
                    </div>

                    <!-- Change Due -->
                    <div class="mt-auto p-6 bg-emerald-50 dark:bg-emerald-500/10 rounded-3xl border border-emerald-100 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 flex items-center justify-between">
                        <span class="font-bold text-lg">Change Due</span>
                        <span class="font-black text-3xl">{{ formatMoney(changeDue) }}</span>
                    </div>
                </div>

                 <!-- Card Payment Details (Placeholder) -->
                <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-3xl p-8">
                     <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    <p class="font-bold mb-1">Waiting for terminal...</p>
                    <p class="text-xs">Follow instructions on the card reader.</p>
                </div>

                <!-- Actions -->
                <div class="mt-8 grid grid-cols-2 gap-4">
                    <button @click="closeModal" class="py-5 rounded-3xl font-extrabold text-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button 
                        @click="completeSale"
                        :disabled="!isValidPayment"
                        class="py-5 rounded-3xl font-extrabold text-xl bg-primary-600 text-white shadow-xl shadow-primary-500/30 hover:bg-primary-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                         {{ paymentMethod === 'card' ? 'Charge & Complete' : 'Complete Sale' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useCartStore } from '../stores/cart';
import { db } from '../db/pos-db';

const props = defineProps({
    visible: Boolean,
    currencyCode: { type: String, default: 'USD' }
});

const emit = defineEmits(['close', 'payment-complete']);

const cart = useCartStore();
const paymentMethod = ref('cash');
const amountTenderedInput = ref(''); 

// Reset when visible
watch(() => props.visible, (newVal) => {
    if (newVal) {
        paymentMethod.value = 'cash';
        amountTenderedInput.value = '';
    }
});

const closeModal = () => {
    emit('close');
};

const currencySymbol = computed(() => {
    // Hacky way to get symbol from code
    return (0).toLocaleString('en-US', { style: 'currency', currency: props.currencyCode, minimumFractionDigits: 0, maximumFractionDigits: 0 }).replace(/\d/g, '').trim();
});

const amountTenderedMinor = computed(() => {
    if (!amountTenderedInput.value) return 0;
    return Math.round(parseFloat(amountTenderedInput.value) * 100);
});

const changeDue = computed(() => {
    if (paymentMethod.value !== 'cash') return 0;
    return Math.max(0, amountTenderedMinor.value - cart.total);
});

const isValidPayment = computed(() => {
    if (!props.visible) return false;
    if (paymentMethod.value === 'cash') {
         return amountTenderedMinor.value >= cart.total;
    }
    return true; // Assume card is valid for now
});

const quickAmounts = computed(() => {
    const total = cart.total; 
    const amounts = [];
    
    // Suggest rounding up to next note/bill sizes
    // Logic: if 1250 ($12.50), suggest 1300, 1500, 2000, 5000, 10000
    
    const nextDollar = Math.ceil(total / 100) * 100;
    if (nextDollar > total) amounts.push(nextDollar);
    
    [500, 1000, 2000, 5000, 10000].forEach(note => {
        if (note > total && !amounts.includes(note)) {
             // Basic logic to prevent too many buttons
             if (amounts.length < 4) amounts.push(note);
        }
    });

    return amounts.sort((a, b) => a - b);
});

const setTendered = (amountMinor) => {
    amountTenderedInput.value = (amountMinor / 100).toFixed(2);
};

const formatMoney = (amount) => {
    if (amount === undefined || amount === null) return '$0.00';
    const val = Number(amount) / 100;
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: props.currencyCode }).format(val);
};

const completeSale = () => {
    if (!isValidPayment.value) return;
    
    const paymentData = {
        method: paymentMethod.value,
        amount_tendered: paymentMethod.value === 'cash' ? amountTenderedMinor.value : cart.total, // For card assume exact
        change_given: changeDue.value
    };
    
    emit('payment-complete', paymentData);
};
</script>
