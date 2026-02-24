<template>
    <div v-if="visible" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="p-6 text-center border-b dark:border-gray-800">
                <div class="w-16 h-16 bg-amber-50 dark:bg-amber-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">Manager Approval Required</h2>
                <p class="text-sm text-gray-500 mt-1">Ask a manager to enter their PIN to approve this return.</p>
            </div>

            <!-- PIN Display -->
            <div class="p-6 space-y-6">
                <div class="flex justify-center gap-3">
                    <div
                        v-for="i in 4"
                        :key="i"
                        class="w-12 h-14 rounded-2xl border-2 flex items-center justify-center text-2xl font-black transition-all duration-200"
                        :class="[
                            pin.length >= i
                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-transparent'
                        ]"
                    >
                        {{ pin.length >= i ? '●' : '○' }}
                    </div>
                </div>

                <!-- Error message -->
                <div v-if="errorMessage" class="bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 p-3 rounded-2xl text-sm font-bold text-center animate-in slide-in-from-top-2 duration-300">
                    {{ errorMessage }}
                </div>

                <!-- Numpad -->
                <div class="grid grid-cols-3 gap-3">
                    <button
                        v-for="digit in ['1','2','3','4','5','6','7','8','9','','0','⌫']"
                        :key="digit"
                        @click="handleDigit(digit)"
                        :disabled="!digit || (digit !== '⌫' && pin.length >= 6)"
                        class="h-14 rounded-2xl font-black text-xl transition-all active:scale-95 disabled:opacity-30"
                        :class="[
                            digit === '⌫'
                                ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/40'
                                : digit === ''
                                    ? 'invisible pointer-events-none'
                                    : 'bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'
                        ]"
                    >
                        {{ digit }}
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t dark:border-gray-800 flex gap-3">
                <button
                    @click="$emit('cancel')"
                    class="flex-1 py-4 rounded-2xl font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="submitPin"
                    :disabled="pin.length < 4 || verifying"
                    class="flex-1 py-4 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-black shadow-lg shadow-amber-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-95 flex items-center justify-center gap-2"
                >
                    <span v-if="verifying" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    {{ verifying ? 'Verifying...' : 'Approve' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import * as syncService from '../services/sync-service';

const props = defineProps({
    visible: Boolean,
    returnId: [Number, String, null],
});

const emit = defineEmits(['cancel', 'approved']);

const pin = ref('');
const verifying = ref(false);
const errorMessage = ref('');

const handleDigit = (digit) => {
    if (digit === '⌫') {
        pin.value = pin.value.slice(0, -1);
        errorMessage.value = '';
        return;
    }
    if (digit && pin.value.length < 6) {
        pin.value += digit;
        errorMessage.value = '';
    }
};

const submitPin = async () => {
    if (pin.value.length < 4 || !props.returnId) return;

    verifying.value = true;
    errorMessage.value = '';

    try {
        const result = await syncService.verifyManagerPin(props.returnId, pin.value);

        if (result.approved) {
            emit('approved', result);
        } else {
            errorMessage.value = result.message || 'Incorrect PIN. Please try again.';
            // Shake and reset PIN after invalid attempt
            setTimeout(() => { pin.value = ''; }, 600);
        }
    } catch (e) {
        const msg = e.response?.data?.message || 'Verification failed. Please try again.';
        errorMessage.value = msg;
        setTimeout(() => { pin.value = ''; }, 600);
    } finally {
        verifying.value = false;
    }
};
</script>
