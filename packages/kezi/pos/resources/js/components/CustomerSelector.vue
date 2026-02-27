<template>
    <div class="relative w-full px-6 py-3 border-b dark:border-gray-800 bg-white dark:bg-gray-900 z-50">
        <div class="relative group">
            <button 
                @click="isOpen = !isOpen"
                class="w-full h-12 flex items-center justify-between px-4 bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-2xl border-2 border-transparent transition-all"
                :class="{ 'border-primary-500 ring-4 ring-primary-500/10': isOpen }"
            >
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="text-left truncate">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 leading-none mb-0.5">Customer</p>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">
                            {{ cartStore.currentCustomer ? cartStore.currentCustomer.name : 'Walk-in Customer' }}
                        </p>
                    </div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <transition 
                enter-active-class="transition duration-100 ease-out" 
                enter-from-class="transform scale-95 opacity-0" 
                enter-to-class="transform scale-100 opacity-100" 
                leave-active-class="transition duration-75 ease-in" 
                leave-from-class="transform scale-100 opacity-100" 
                leave-to-class="transform scale-95 opacity-0"
            >
                <div v-if="isOpen" class="absolute left-0 right-0 mt-2 bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden z-[60]">
                    <!-- Search Input -->
                    <div class="p-4 border-b dark:border-gray-800">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </span>
                            <input 
                                v-model="search"
                                type="text"
                                placeholder="Search customers..."
                                class="w-full bg-gray-50 dark:bg-gray-800/50 border-transparent focus:bg-white dark:focus:bg-gray-800 border-2 focus:border-primary-500 rounded-xl py-2.5 pl-9 pr-4 text-sm font-medium outline-none transition-all"
                                autofocus
                                @keydown.down.prevent="navigateResults('down')"
                                @keydown.up.prevent="navigateResults('up')"
                                @keydown.enter.prevent="selectHighlighted"
                            >
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="max-h-64 overflow-y-auto overscroll-contain">
                        <!-- Default Walk-in Option -->
                        <button 
                            @click="selectCustomer(null)"
                            class="w-full px-6 py-4 flex items-center justify-between hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors group border-b dark:border-gray-800/50"
                            :class="{ 'bg-primary-50 dark:bg-primary-900/10': highlightedIndex === -1 }"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-white dark:group-hover:bg-gray-900 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-gray-900 dark:text-white text-sm">Walk-in Customer</p>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Default Option</p>
                                </div>
                            </div>
                            <svg v-if="!cartStore.currentCustomer" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Customer List -->
                        <div v-if="filteredResults.length > 0">
                            <button 
                                v-for="(customer, index) in filteredResults" 
                                :key="customer.id"
                                @click="selectCustomer(customer)"
                                class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors group border-b dark:border-gray-800/50 last:border-0"
                                :class="{ 'bg-gray-50 dark:bg-gray-800': highlightedIndex === index }"
                            >
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 shrink-0">
                                        {{ customer.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="text-left overflow-hidden">
                                        <p class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ customer.name }}</p>
                                        <p class="text-xs font-medium text-gray-500 truncate">{{ customer.email || customer.phone || 'No contact info' }}</p>
                                    </div>
                                </div>
                                <svg v-if="cartStore.currentCustomer?.id === customer.id" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        
                        <div v-else-if="search && filteredResults.length === 0" class="p-8 text-center">
                            <div class="w-12 h-12 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center text-gray-400 mx-auto mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <p class="text-sm font-bold text-gray-500">No customers found</p>
                            <p class="text-xs text-gray-400 px-4 mt-1">Try searching by name, email or phone number</p>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { db } from '../db/pos-db';
import { useCartStore } from '../stores/cart';

const cartStore = useCartStore();
const isOpen = ref(false);
const search = ref('');
const allCustomers = ref([]);
const highlightedIndex = ref(-1);

onMounted(async () => {
    await loadCustomers();
});

const loadCustomers = async () => {
    try {
        allCustomers.value = await db.customers.toArray();
    } catch (e) {
        console.error('Failed to load customers from IndexedDB', e);
    }
};

const filteredResults = computed(() => {
    if (!search.value) return allCustomers.value.slice(0, 50);
    
    const query = search.value.toLowerCase();
    return allCustomers.value.filter(c => 
        c.name?.toLowerCase().includes(query) || 
        c.email?.toLowerCase().includes(query) || 
        c.phone?.toLowerCase().includes(query)
    ).slice(0, 50);
});

const selectCustomer = (customer) => {
    cartStore.setCustomer(customer);
    isOpen.value = false;
    search.value = '';
    highlightedIndex.value = -1;
};

const navigateResults = (direction) => {
    if (direction === 'down') {
        if (highlightedIndex.value < filteredResults.value.length - 1) {
            highlightedIndex.value++;
        }
    } else {
        if (highlightedIndex.value > -1) {
            highlightedIndex.value--;
        }
    }
};

const selectHighlighted = () => {
    if (highlightedIndex.value === -1) {
        selectCustomer(null);
    } else if (filteredResults.value[highlightedIndex.value]) {
        selectCustomer(filteredResults.value[highlightedIndex.value]);
    }
};

// Close when clicking outside
onMounted(() => {
    const closeHandler = (e) => {
        if (!e.target.closest('.relative.group')) {
            isOpen.value = false;
        }
    };
    window.addEventListener('click', closeHandler);
    return () => window.removeEventListener('click', closeHandler);
});
</script>
