<template>
    <!-- Backdrop -->
    <div v-if="visible" @click="$emit('close')" class="fixed inset-0 z-[90] bg-gray-900/40 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Panel -->
    <transition name="slide-panel">
        <div v-if="visible" class="fixed inset-y-0 right-0 z-[95] w-full max-w-md bg-white dark:bg-gray-900 shadow-2xl flex flex-col transform transition-transform duration-300 ease-in-out">
            <!-- Header -->
            <div class="p-6 border-b dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Order History</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Session #{{ sessionStore.sessionId }}</p>
                </div>
                <button @click="$emit('close')" class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 flex items-center justify-center transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Search -->
            <div class="p-4 border-b dark:border-gray-800 bg-white dark:bg-gray-900 sticky top-0 z-10">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input 
                        v-model="searchQuery" 
                        type="text"
                        placeholder="Search order number or total..." 
                        class="w-full bg-gray-100 dark:bg-gray-800/50 border-0 rounded-xl py-3 pl-10 pr-4 focus:ring-2 focus:ring-primary-500 transition-all dark:text-white"
                    >
                </div>
            </div>
            
            <!-- Order List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <div v-if="loading" class="flex justify-center py-10">
                    <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-primary-600"></div>
                </div>

                <div v-else-if="filteredOrders.length === 0" class="text-center py-10 text-gray-500">
                    <p>No orders found in this session.</p>
                </div>

                <div 
                    v-for="order in filteredOrders" 
                    :key="order.id" 
                    class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition-shadow group"
                >
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white block">{{ order.order_number }}</span>
                            <span class="text-xs text-gray-400">{{ formatTime(order.ordered_at) }}</span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="font-black text-gray-900 dark:text-white">{{ formatMoney(order.total_amount) }}</span>
                            <span :class="syncStatusClass(order)" class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide border">
                                {{ syncStatusLabel(order) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 pt-3 border-t dark:border-gray-700">
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="capitalize bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ order.payment_method }}</span>
                            <span v-if="order.items_count">{{ order.items_count }} items</span>
                        </div>
                        <button 
                            @click="handleReprint(order.id)" 
                            class="text-primary-600 hover:text-primary-700 text-xs font-bold flex items-center gap-1 py-1 px-2 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Reprint
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Summary Footer -->
            <div class="p-6 border-t dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex justify-between items-center mb-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>Total Orders</span>
                    <span class="font-bold">{{ filteredOrders.length }}</span>
                </div>
                <div class="flex justify-between items-center text-lg text-gray-900 dark:text-white">
                    <span>Session Revenue</span>
                    <span class="font-black text-primary-600">{{ formatMoney(totalRevenue) }}</span>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSessionStore } from '../stores/session';
import { db } from '../db/pos-db';
import { useReceipt } from '../composables/useReceipt';

const props = defineProps({
    visible: Boolean,
});

const emit = defineEmits(['close']);

const sessionStore = useSessionStore();
const { printReceipt } = useReceipt();

const orders = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const currentCurrency = ref('USD');

// Load settings on mount
onMounted(async () => {
    const setting = await db.settings.get('company_currency');
    if (setting && setting.value) {
        currentCurrency.value = setting.value.code || 'USD';
    }
});

// Watch visibility to reload data
watch(() => props.visible, async (isVisible) => {
    if (isVisible) {
        await loadOrders();
    }
});

const loadOrders = async () => {
    loading.value = true;
    try {
        const sessionId = sessionStore.sessionId;
        if (!sessionId) {
            orders.value = [];
            return;
        }
        
        // Fetch all orders
        const allOrders = await db.orders.toArray();
        
        // Filter by session ID in memory (since it's not indexed)
        // Also fetch line counts if needed, but for now just count items locally if we had them or store item count on order
        // The order object structure in task description doesn't explicitly have items_count, 
        // but we might want to query it or just skip it.
        // Let's check the task description's example order object:
        /*
        {
            id: ...,
            uuid: ...,
            pos_session_id: 5,
            ...
        }
        */
       
        // We'll proceed with filtering.
        orders.value = allOrders
            .filter(o => o.pos_session_id === sessionId)
            .sort((a, b) => new Date(b.ordered_at) - new Date(a.ordered_at)); // Newest first

    } catch (e) {
        console.error('Failed to load history', e);
    } finally {
        loading.value = false;
    }
};

const filteredOrders = computed(() => {
    if (!searchQuery.value) return orders.value;
    const q = searchQuery.value.toLowerCase();
    
    return orders.value.filter(o => 
        o.order_number.toLowerCase().includes(q) || 
        (o.total_amount / 100).toString().includes(q)
    );
});

const totalRevenue = computed(() => {
    // Sum total_amount of all orders in the current filtered view? 
    // Or normally it should be session total (all orders in list).
    // Let's sum the filtered ones so searching shows sub-totals which is often useful.
    return filteredOrders.value.reduce((sum, o) => sum + (o.total_amount || 0), 0);
});

const formatMoney = (amount) => {
    if (amount === undefined || amount === null) return '$0.00';
    const val = Number(amount) / 100;
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currentCurrency.value }).format(val);
};

const formatTime = (isoString) => {
    if (!isoString) return '';
    return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const syncStatusLabel = (order) => {
    if (order.sync_status === 'synced') return 'Synced';
    if (order.sync_status === 'failed') return 'Failed';
    return 'Pending';
};

const syncStatusClass = (order) => {
    if (order.sync_status === 'synced') {
        return 'text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800';
    }
    if (order.sync_status === 'failed') {
        return 'text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/30 border-rose-200 dark:border-rose-800';
    }
    return 'text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800';
};

const handleReprint = async (orderId) => {
    await printReceipt(orderId);
};
</script>

<style scoped>
.slide-panel-enter-active,
.slide-panel-leave-active {
    transition: transform 0.3s ease-in-out;
}

.slide-panel-enter-from,
.slide-panel-leave-to {
    transform: translateX(100%);
}
</style>
