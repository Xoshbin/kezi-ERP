<template>
    <div class="pos-container h-full w-full flex flex-col bg-gray-100 dark:bg-gray-950 font-sans antialiased text-gray-900 dark:text-gray-100 overflow-hidden">
        <!-- Top Bar -->
        <header class="h-16 bg-white dark:bg-gray-900 shadow-sm border-b dark:border-gray-800 flex items-center justify-between px-6 z-10">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight">Universal POS</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-none">Kezi ERP Systems</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Status Indicators -->
                <div class="flex items-center gap-3 text-xs font-medium">
                    <div :class="[
                        'flex items-center gap-1.5 px-2.5 py-1 rounded-full border transition-colors',
                        connectivity.isOnline 
                            ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-500/20' 
                            : 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-100 dark:border-rose-500/20'
                    ]">
                        <span :class="['w-1.5 h-1.5 rounded-full animate-pulse', connectivity.isOnline ? 'bg-emerald-500' : 'bg-rose-500']"></span>
                        {{ connectivity.isOnline ? (connectivity.isSyncing ? 'Syncing...' : 'Online') : 'Offline' }}
                    </div>
                </div>

                <!-- User Profile -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold">Cashier</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider">Main Register</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-800 border-2 border-white dark:border-gray-700"></div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 flex overflow-hidden">
            <!-- Left Sidebar - Navigation / Categories -->
            <nav class="w-20 bg-white dark:bg-gray-900 border-r dark:border-gray-800 flex flex-col items-center py-6 gap-6 shadow-sm z-5">
                <button 
                    class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 group relative bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400"
                    @click="filterCategory(null)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">All Items</span>
                </button>
                <!-- Categories -->
                <button 
                    v-for="cat in productsStore.categories" 
                    :key="cat.id"
                    @click="filterCategory(cat.id)"
                    class="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200 group relative text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-600 dark:hover:text-gray-200"
                    :class="{ 'bg-primary-50 text-primary-600': productsStore.selectedCategory === cat.id }"
                >
                    <!-- Placeholder Icon -->
                    <span class="font-bold text-xs">{{ cat.name.substring(0,2).toUpperCase() }}</span>
                    <span class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-[10px] rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50">{{ cat.name }}</span>
                </button>
            </nav>

            <!-- Product Grid -->
            <section class="flex-1 p-6 overflow-y-auto bg-gray-50 dark:bg-gray-950/50">
                <div class="max-w-6xl mx-auto">
                    <!-- Search & Filter -->
                    <div class="flex items-center gap-4 mb-8">
                        <div class="relative flex-1 group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-primary-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </span>
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Search products, barcodes..." 
                                class="w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 rounded-2xl py-3 pl-12 pr-4 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all outline-none shadow-sm dark:text-white"
                            >
                        </div>
                        <button @click="loadProducts" class="bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-4 py-3 rounded-2xl font-semibold shadow-sm hover:bg-gray-50 transition-all">
                            Refresh
                        </button>
                    </div>

                    <!-- Grid -->
                    <div v-if="productsStore.loading" class="flex justify-center py-20">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-600"></div>
                    </div>
                    
                    <div v-else-if="productsStore.filteredProducts.length === 0" class="text-center py-20 text-gray-500">
                        No products found.
                    </div>

                    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                        <div 
                            v-for="product in productsStore.filteredProducts" 
                            :key="product.id" 
                            @click="addToCart(product)"
                            class="product-card group bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-4 transition-all duration-300 hover:shadow-2xl hover:shadow-gray-200/50 dark:hover:shadow-black/50 hover:-translate-y-1 cursor-pointer"
                        >
                            <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-2xl mb-4 overflow-hidden relative flex items-center justify-center text-gray-300">
                                <!-- Placeholder Image -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="absolute top-2 right-2 px-2 py-1 bg-white/90 dark:bg-gray-900/90 backdrop-blur rounded-lg text-[10px] font-bold text-primary-600">
                                    {{ product.available_quantity > 0 ? 'IN STOCK' : 'OUT OF STOCK' }}
                                </div>
                            </div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-1 group-hover:text-primary-600 transition-colors line-clamp-2 min-h-[2.5em]">{{ product.name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ product.sku || 'No SKU' }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-black text-gray-900 dark:text-white">{{ formatMoney(product.unit_price) }}</span>
                                <button class="w-8 h-8 rounded-full bg-gray-50 dark:bg-gray-800 text-gray-400 group-hover:bg-primary-600 group-hover:text-white transition-all duration-300 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Cart Section -->
            <aside class="w-96 bg-white dark:bg-gray-900 border-l dark:border-gray-800 flex flex-col shadow-2xl z-10 overflow-hidden">
                <div class="p-6 border-b dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">Current Order</h2>
                        <p class="text-xs text-gray-400 leading-none">Order #Local-Draft</p>
                    </div>
                    <button @click="cart.clearCart" class="text-gray-400 hover:text-red-500 transition-colors" title="Clear Cart">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>

                <!-- Cart Items List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div v-if="cart.items.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p>Cart is empty</p>
                    </div>
                    
                    <div v-for="item in cart.items" :key="item.id" class="cart-item bg-gray-50 dark:bg-gray-800/40 rounded-2xl p-3 flex gap-4 transition-all hover:bg-white dark:hover:bg-gray-800 border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                        <div class="w-16 h-16 bg-white dark:bg-gray-900 rounded-xl border dark:border-gray-700 flex-shrink-0 flex items-center justify-center text-xs font-bold text-gray-400">
                            {{ item.sku || 'IMG' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-bold truncate">{{ item.name }}</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500">{{ formatMoney(item.unit_price) }} × {{ item.quantity }}</span>
                                <span class="text-sm font-black">{{ formatMoney(item.unit_price * item.quantity) }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <button @click="cart.updateQuantity(item.id, item.quantity - 1)" class="w-6 h-6 rounded-lg bg-white dark:bg-gray-700 border dark:border-gray-600 flex items-center justify-center hover:bg-gray-100">-</button>
                                <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                <button @click="cart.addItem(item)" class="w-6 h-6 rounded-lg bg-white dark:bg-gray-700 border dark:border-gray-600 flex items-center justify-center hover:bg-gray-100">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totals & Payment -->
                <div class="p-6 bg-gray-50 dark:bg-gray-800/20 border-t dark:border-gray-800 space-y-6">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Subtotal</span>
                            <span class="font-medium">{{ formatMoney(cart.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500">
                           <!-- Hardcoded tax rate visualization for now, logic in store -->
                            <span>Tax (Approx)</span>
                            <span class="font-medium">{{ formatMoney(cart.tax) }}</span>
                        </div>
                        <div class="flex justify-between items-end pt-4 border-t dark:border-gray-700">
                            <span class="text-lg font-bold">Total</span>
                            <span class="text-3xl font-black text-primary-600">{{ formatMoney(cart.total) }}</span>
                        </div>
                    </div>

                    <button class="pay-button w-full bg-primary-600 hover:bg-primary-700 text-white py-5 rounded-3xl font-extrabold text-xl shadow-xl shadow-primary-500/30 flex items-center justify-center gap-3 transition-all active:scale-95 group" :disabled="cart.items.length === 0" :class="{'opacity-50 cursor-not-allowed': cart.items.length === 0}">
                        Confirm & Pay
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </aside>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useConnectivityStore } from './stores/connectivity';
import { useCartStore } from './stores/cart';
import { useProductsStore } from './stores/products';
import { startSyncWorker } from './services/sync-worker';
import { db } from './db/pos-db';

const connectivity = useConnectivityStore();
const cart = useCartStore();
const productsStore = useProductsStore();

const currentCurrency = ref('USD');

onMounted(async () => {
    // Initialize connectivity listeners
    if (typeof navigator !== 'undefined') {
        connectivity.setOnline(navigator.onLine);
    }
    connectivity.initListeners();
    
    // Start background worker
    startSyncWorker();
    
    // Initial sync and load logic
    try {
        if (connectivity.isOnline) {
             // If online, sync first then load
             await productsStore.syncAndReload();
        } else {
             // Offline, just load
             await productsStore.loadFromDb();
        }
        
        // Load currency from DB (synced)
        const setting = await db.settings.get('company_currency');
        if (setting && setting.value) {
            currentCurrency.value = setting.value.code || 'USD';
        }
    } catch(e) {
        console.error('Initial load failed', e);
        // Ensure at least loading is false
        productsStore.loading = false;
    }
});

// Alias for refresh button
const loadProducts = async () => {
    await productsStore.syncAndReload();
};

const filterCategory = (id) => {
    productsStore.selectCategory(id);
};

const addToCart = (product) => {
    cart.addItem(product);
};

const searchQuery = computed({
    get: () => productsStore.searchQuery,
    set: (val) => productsStore.setSearchQuery(val)
});

// Helper for money formatting (assuming minor units, e.g. cents)
const formatMoney = (amount) => {
    if (amount === undefined || amount === null) return '$0.00';
    // Amount is in minor units (integer). Divide by 100.
    const val = Number(amount) / 100;
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currentCurrency.value }).format(val);
};

</script>

<style>
@import "tailwindcss";

:root {
    --primary-50: #fefce8;
    --primary-100: #fef9c3;
    --primary-200: #fef08a;
    --primary-300: #fde047;
    --primary-400: #facc15;
    --primary-500: #eab308;
    --primary-600: #ca8a04;
    --primary-700: #a16207;
    --primary-800: #854d0e;
    --primary-900: #713f12;
    --primary-950: #422006;
}

.pos-container {
    height: 100vh;
}

.product-card, .cart-item, .pay-button {
    backface-visibility: hidden;
    -webkit-font-smoothing: antialiased;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #1e293b;
}
</style>
