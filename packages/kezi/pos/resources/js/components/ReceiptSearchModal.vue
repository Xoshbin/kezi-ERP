<template>
    <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="p-6 border-b dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Lookup Receipt</h2>
                    <p class="text-sm text-gray-500">Search for a previous transaction to start a return</p>
                </div>
                <button @click="$emit('close')" class="w-10 h-10 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center justify-center text-gray-400 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="p-6 bg-gray-50 dark:bg-gray-800/50 space-y-4">
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 group-focus-within:text-primary-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search by order # (e.g. POS-1234), customer name, or phone..."
                        class="w-full bg-white dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-2xl py-4 pl-14 pr-4 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-all outline-none font-medium text-lg dark:text-white shadow-sm"
                        @input="debouncedSearch"
                        autofocus
                    >
                </div>
                
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                    <button 
                        v-for="filter in quickFilters" 
                        :key="filter.id"
                        @click="setQuickFilter(filter.id)"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap border',
                            activeFilter === filter.id 
                                ? 'bg-primary-600 text-gray-900 border-primary-600 shadow-lg shadow-primary-500/20' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750'
                        ]"
                    >
                        {{ filter.label }}
                    </button>
                </div>
            </div>

            <!-- Results -->
            <div class="flex-1 overflow-y-auto p-6 min-h-[400px]">
                <div v-if="loading" class="flex flex-col items-center justify-center h-full py-20">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500/20 border-t-primary-500 mb-4"></div>
                    <p class="text-sm font-bold text-gray-500 animate-pulse">Searching Transaction History...</p>
                </div>

                <div v-else-if="orders.length === 0" class="flex flex-col items-center justify-center h-full py-20 text-center">
                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-3xl flex items-center justify-center mb-6 text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">No Transactions Found</h3>
                    <p class="text-gray-500 max-w-xs">Double check the order number or try searching by customer name.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="order in orders" 
                        :key="order.id"
                        @click="selectOrder(order)"
                        class="group cursor-pointer p-4 rounded-3xl border-2 transition-all duration-300"
                        :class="[
                            'bg-white dark:bg-gray-800/50 hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-black/50 hover:-translate-y-1',
                            selectedOrderId === order.id 
                                ? 'border-primary-500 ring-4 ring-primary-500/10' 
                                : 'border-gray-100 dark:border-gray-800 hover:border-primary-300 dark:hover:border-primary-700'
                        ]"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-black text-lg tracking-tight group-hover:text-primary-600 transition-colors">{{ order.order_number }}</h4>
                                <p class="text-xs text-gray-500 font-medium">{{ formatDate(order.ordered_at) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-gray-900 dark:text-white">{{ formatMoney(order.total_amount) }}</p>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" :class="order.status === 'paid' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30' : 'bg-gray-100 text-gray-600'">
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-750 flex items-center justify-center text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 truncate">{{ order.customer?.name || 'Walk-in Customer' }}</span>
                        </div>

                        <div class="pt-4 border-t dark:border-gray-700 flex items-center justify-between">
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest">{{ order.items_count }} items</span>
                            <div class="flex items-center gap-1.5 text-xs font-black">
                                <template v-if="order.eligible">
                                    <span class="text-emerald-500">ELIGIBLE FOR RETURN</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                                <template v-else>
                                    <span class="text-rose-500">NOT ELIGIBLE</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900 flex justify-between items-center">
                <p class="text-xs text-gray-500 font-medium">Tip: Use exact order number for faster lookups.</p>
                <div class="flex gap-4">
                    <button @click="$emit('close')" class="px-6 py-3 rounded-2xl font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        Cancel
                    </button>
                    <button 
                        @click="handleSelect" 
                        :disabled="!selectedOrder || !selectedOrder.eligible"
                        class="px-8 py-3 bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed text-gray-900 rounded-2xl font-black shadow-lg shadow-primary-500/20 active:scale-95 transition-all"
                    >
                        Review Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useSessionStore } from '../stores/session';
import * as syncService from '../services/sync-service';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

const props = defineProps({
    visible: Boolean
});

const emit = defineEmits(['close', 'select-order']);

const sessionStore = useSessionStore();
const searchQuery = ref('');
const orders = ref([]);
const loading = ref(false);
const selectedOrderId = ref(null);
const selectedOrder = ref(null);
const activeFilter = ref('today');

const quickFilters = [
    { id: 'today', label: 'Today' },
    { id: 'yesterday', label: 'Yesterday' },
    { id: 'week', label: 'Past 7 Days' },
    { id: 'all', label: 'All History' }
];

let searchTimeout = null;

const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 500);
};

const setQuickFilter = (id) => {
    activeFilter.value = id;
    performSearch();
};

const performSearch = async () => {
    if (searchQuery.value.length < 3 && !activeFilter.value) {
        orders.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await syncService.quickSearchOrders(
            searchQuery.value,
            activeFilter.value === 'all' ? null : sessionStore.sessionId // Example filter usage
        );
        
        // For each order, we need to check eligibility
        // In a real app, the API should return this. 
        // For now, let's assume all are eligible or fetch details.
        
        const results = response.data || [];
        
        // Enrich with eligibility (Mocking for now unless API is ready)
        orders.value = results.map(o => ({
            ...o,
            eligible: true // Placeholder
        }));
        
    } catch (e) {
        console.error('Search transactions failed', e);
    } finally {
        loading.value = false;
    }
};

const selectOrder = (order) => {
    selectedOrderId.value = order.id;
    selectedOrder.value = order;
};

const handleSelect = async () => {
    if (!selectedOrder.value) return;
    
    loading.value = true;
    try {
        // Fetch full details
        const details = await syncService.getOrderDetails(selectedOrder.value.id);
        const eligibility = await syncService.checkReturnEligibility(selectedOrder.value.id);
        
        emit('select-order', {
            ...details.data,
            eligible: eligibility.eligible,
            eligibility_reasons: eligibility.reasons
        });
    } catch (e) {
        console.error('Fetch order details failed', e);
        alert('Failed to load transaction details.');
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => {
    return dayjs(date).fromNow();
};

const formatMoney = (amount) => {
    const val = Number(amount) / sessionStore.decimalFactor;
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: sessionStore.currencyCode,
        minimumFractionDigits: sessionStore.decimalPlaces
    }).format(val);
};

onMounted(() => {
    performSearch();
});
</script>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
