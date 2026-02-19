<template>
    <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-gray-900/40 backdrop-blur-sm transition-all duration-300">
        <div 
            class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-gray-100 dark:border-gray-800 animate-in zoom-in-95 fade-in duration-200"
            @click.stop
        >
            <!-- Header -->
            <div class="px-6 py-4 border-b dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Discount for {{ item?.name }}
                </h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                <!-- Type Toggle -->
                <div class="bg-gray-100 dark:bg-gray-800 p-1 rounded-xl flex gap-1">
                    <button 
                        @click="discountType = 'percentage'"
                        :class="[
                            'flex-1 py-2 px-4 rounded-lg text-sm font-bold transition-all',
                            discountType === 'percentage' 
                                ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' 
                                : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                        ]"
                    >
                        Percentage
                    </button>
                    <button 
                        @click="discountType = 'fixed'"
                        :class="[
                            'flex-1 py-2 px-4 rounded-lg text-sm font-bold transition-all',
                            discountType === 'fixed' 
                                ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' 
                                : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
                        ]"
                    >
                        Fixed Amount
                    </button>
                </div>

                <!-- Input -->
                <div class="relative group">
                    <input 
                        v-model.number="discountValue"
                        type="number" 
                        :placeholder="discountType === 'percentage' ? '10' : '5.00'"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-primary-500 rounded-2xl py-4 px-6 text-2xl font-black text-center outline-none transition-all dark:text-white"
                        ref="inputRef"
                        @keyup.enter="apply"
                    >
                    <div class="absolute inset-y-0 right-6 flex items-center pointer-events-none">
                        <span class="text-xl font-bold text-gray-400">
                            {{ discountType === 'percentage' ? '%' : currencyCode }}
                        </span>
                    </div>
                </div>

                <!-- Savings Preview -->
                <div class="bg-rose-50 dark:bg-rose-900/20 rounded-2xl p-4 flex items-center justify-between border border-rose-100 dark:border-rose-900/30">
                    <span class="text-sm font-bold text-rose-600 dark:text-rose-400">Potential Savings</span>
                    <span class="text-lg font-black text-rose-600 dark:text-rose-400">
                        -{{ formatMoney(savingsAmount) }}
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t dark:border-gray-800 flex gap-3">
                <button 
                    @click="$emit('close')"
                    class="flex-1 py-3 px-4 rounded-2xl font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    @click="apply"
                    class="flex-1 py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white rounded-2xl font-bold shadow-lg shadow-primary-500/30 transition-all active:scale-95"
                >
                    Apply Discount
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, nextTick } from 'vue';

const props = defineProps({
    visible: Boolean,
    item: Object,
    currencyCode: String
});

const emit = defineEmits(['close', 'apply']);

const discountType = ref('percentage');
const discountValue = ref(0);
const inputRef = ref(null);

watch(() => props.visible, (val) => {
    if (val && props.item) {
        discountType.ref = props.item.discount_type || 'percentage';
        // If fixed, convert from minor units to major units for user display
        if (props.item.discount_type === 'fixed') {
            discountValue.value = Number(props.item.discount_value) / 100;
        } else {
            discountValue.value = props.item.discount_value || 0;
        }
        
        nextTick(() => {
            inputRef.value?.focus();
            inputRef.value?.select();
        });
    }
});

const savingsAmount = computed(() => {
    if (!props.item) return 0;
    const lineSubtotal = props.item.unit_price * props.item.quantity;
    
    if (discountType.value === 'percentage') {
        return Math.round(lineSubtotal * (Number(discountValue.value || 0) / 100));
    } else {
        // Convert input (major) to minor units for calculation
        const minorValue = Math.round(Number(discountValue.value || 0) * 100);
        return Math.min(minorValue, lineSubtotal);
    }
});

const apply = () => {
    let finalValue = Number(discountValue.value) || 0;
    
    // If fixed, store as minor units
    if (discountType.value === 'fixed') {
        finalValue = Math.round(finalValue * 100);
    }
    
    emit('apply', {
        type: discountType.value,
        value: finalValue
    });
};

const formatMoney = (amount) => {
    const val = Number(amount) / 100;
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: props.currencyCode || 'USD' 
    }).format(val);
};
</script>
