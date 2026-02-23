<template>
    <div class="pos-container h-full w-full flex flex-col bg-gray-100 dark:bg-gray-950 font-sans antialiased text-gray-900 dark:text-gray-100 overflow-hidden">
        <!-- Session Gating Modal -->
        <OpenSessionModal v-if="sessionStore.showOpenSessionModal" />

        <!-- Payment Modal -->
        <PaymentModal 
            :visible="showPaymentModal" 
            :currency-code="currentCurrency"
            @close="showPaymentModal = false"
            @payment-complete="handlePaymentComplete"
        />

        <!-- Close Session Modal -->
        <CloseSessionModal 
            :visible="showCloseSessionModal"
            :currency-code="currentCurrency"
            @close="showCloseSessionModal = false"
            @session-closed="onSessionClosed"
        />

        <!-- Order History Panel -->
        <OrderHistoryPanel
            :visible="showOrderHistory"
            @close="showOrderHistory = false"
        />

        <!-- Success Overlay -->
        <div v-if="orderSuccess" class="fixed inset-0 z-[110] bg-white dark:bg-gray-900 flex flex-col items-center justify-center text-center p-6 transition-all duration-300">
            <div class="w-24 h-24 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-6 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Order Confirmed!</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-8 font-mono text-lg">{{ orderSuccess.orderNumber }}</p>
            
            <div class="space-y-2 mb-10">
                <p class="text-4xl font-black text-primary-600">{{ formatMoney(orderSuccess.total) }}</p>
                <p v-if="orderSuccess.method === 'cash'" class="text-gray-500">
                    Change Given: <span class="font-bold text-gray-900 dark:text-white">{{ formatMoney(orderSuccess.change) }}</span>
                </p>
            </div>
            
            <div class="flex gap-4">
                <button @click="printLastReceipt" class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 px-6 py-4 rounded-2xl font-bold text-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Print Receipt
                </button>
                <button @click="dismissSuccess" class="bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-8 py-4 rounded-2xl font-bold text-lg hover:scale-105 transition-transform shadow-xl">
                    New Sale
                </button>
            </div>
        </div>

        <!-- Discount Popover -->
        <DiscountPopover 
            :visible="discountModal.visible"
            :item="discountModal.item"
            :currency-code="currentCurrency"
            @close="discountModal.visible = false"
            @apply="applyItemDiscount"
        />

        <ScanToast 
            :visible="scanToast.visible" 
            :type="scanToast.type" 
            :message="scanToast.message" 
        />

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
                <!-- Session Info -->
                <div v-if="sessionStore.hasActiveSession" class="hidden lg:flex items-center gap-2 px-4 py-1.5 bg-gray-50 dark:bg-gray-800 rounded-full border dark:border-gray-700">
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                        Session #{{ sessionStore.sessionId }} · {{ sessionStore.profileName }}
                    </span>
                </div>

                <button 
                    v-if="sessionStore.hasActiveSession"
                    @click="showOrderHistory = true"
                    class="w-8 h-8 rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 transition-all flex items-center justify-center border dark:border-gray-700"
                    title="Order History (F9)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </button>

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

                <!-- User Profile & Actions -->
                <div class="flex items-center gap-3 pl-6 border-l dark:border-gray-800">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold">{{ sessionStore.userName || 'Cashier' }}</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider">{{ sessionStore.profileName || 'No Profile' }}</p>
                    </div>
                    
                    <button 
                        v-if="sessionStore.hasActiveSession"
                        @click="handleCloseSession" 
                        class="w-10 h-10 rounded-xl bg-gray-50 hover:bg-rose-50 dark:bg-gray-800 dark:hover:bg-rose-500/10 text-gray-400 hover:text-rose-600 transition-all flex items-center justify-center border dark:border-gray-700 hover:border-rose-200"
                        title="Close Session (F10)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>

                    <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-800 border-2 border-white dark:border-gray-700 shadow-sm overflow-hidden flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main :class="{'pointer-events-none opacity-50 blur-sm': !sessionStore.hasActiveSession}" class="flex-1 flex overflow-hidden transition-all duration-300">
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
                                ref="searchInputRef"
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Search products, barcodes... (F2)" 
                                class="w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 rounded-2xl py-3 pl-12 pr-4 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all outline-none shadow-sm dark:text-white"
                            >
                        </div>
                        <button @click="loadProducts" class="bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-4 py-3 rounded-2xl font-semibold shadow-sm hover:bg-gray-50 transition-all">
                            Refresh
                        </button>
                    </div>

                    <!-- Grid -->
                    <div v-if="productsStore.loading || syncProgress" class="flex flex-col items-center justify-center py-20">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-600 mb-4"></div>
                        <p v-if="syncProgress" class="text-sm font-semibold text-gray-500">{{ syncProgress }}</p>
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
                    <button @click="cart.clearCart" class="text-gray-400 hover:text-red-500 transition-colors" title="Clear Cart (F8)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>

                <!-- Customer Selection -->
                <CustomerSelector />

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

                            <!-- Discount display -->
                            <div v-if="item.discount_type" class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-rose-500 font-bold">
                                    -{{ item.discount_type === 'percentage' ? item.discount_value + '%' : formatMoney(item.discount_value) }}
                                </span>
                                <button @click="cart.clearItemDiscount(item.id)" class="text-gray-400 hover:text-rose-500">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-center gap-2 mt-2">
                                <button @click="cart.updateQuantity(item.id, item.quantity - 1)" class="w-6 h-6 rounded-lg bg-white dark:bg-gray-700 border dark:border-gray-600 flex items-center justify-center hover:bg-gray-100">-</button>
                                <span class="text-xs font-bold w-6 text-center">{{ item.quantity }}</span>
                                <button @click="cart.addItem(item)" class="w-6 h-6 rounded-lg bg-white dark:bg-gray-700 border dark:border-gray-600 flex items-center justify-center hover:bg-gray-100">+</button>
                                
                                <!-- Discount button -->
                                <button 
                                    @click="openItemDiscount(item)"
                                    class="ml-auto w-6 h-6 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center justify-center transition-colors"
                                    title="Add Discount"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </button>
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

                        <!-- Line Discounts (if any) -->
                        <div v-if="cart.lineDiscountTotal > 0" class="flex justify-between text-sm text-rose-500">
                            <span>Line Discounts</span>
                            <span class="font-medium">-{{ formatMoney(cart.lineDiscountTotal) }}</span>
                        </div>

                        <!-- Order Discount -->
                        <div class="flex justify-between text-sm items-center">
                            <button 
                                @click="showOrderDiscountInput = !showOrderDiscountInput"
                                class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ cart.orderDiscountAmount > 0 ? 'Edit Order Discount' : 'Add Order Discount' }}
                            </button>
                            <span v-if="cart.orderDiscountAmount > 0" class="text-rose-500 font-medium">
                                -{{ formatMoney(cart.orderDiscountAmount) }}
                            </span>
                        </div>

                        <!-- Inline order discount input -->
                        <div v-if="showOrderDiscountInput" class="flex gap-2 items-center bg-white dark:bg-gray-800 rounded-xl p-2 border dark:border-gray-700">
                            <input 
                                v-model.number="orderDiscountInput"
                                type="number"
                                min="0" max="100" step="1"
                                placeholder="0"
                                class="w-16 text-center bg-transparent border-b-2 border-primary-500 outline-none font-bold text-sm py-1 dark:text-white"
                                @keyup.enter="applyOrderDiscount"
                            >
                            <span class="text-xs font-bold text-gray-400">%</span>
                            <button @click="applyOrderDiscount" class="text-primary-600 text-xs font-bold px-2 py-1 rounded hover:bg-primary-50">Apply</button>
                            <button @click="clearOrderDiscount" class="text-gray-400 text-xs font-bold px-2 py-1 rounded hover:bg-gray-100">Clear</button>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Tax</span>
                            <span class="font-medium">{{ formatMoney(cart.tax) }}</span>
                        </div>

                        <!-- Show total discount summary if any -->
                        <div v-if="cart.totalDiscount > 0" class="flex justify-between text-sm text-rose-500 font-bold">
                            <span>Total Savings</span>
                            <span>-{{ formatMoney(cart.totalDiscount) }}</span>
                        </div>

                        <div class="flex justify-between items-end pt-4 border-t dark:border-gray-700">
                            <span class="text-lg font-bold">Total</span>
                            <span class="text-3xl font-black text-primary-600">{{ formatMoney(cart.total) }}</span>
                        </div>
                    </div>

                    <button @click="showPaymentModal = true" class="pay-button w-full bg-primary-600 hover:bg-primary-700 !text-gray-900 py-5 rounded-3xl font-extrabold text-xl shadow-xl shadow-primary-500/30 flex items-center justify-center gap-3 transition-all active:scale-95 group" :disabled="cart.items.length === 0" :class="{'opacity-50 cursor-not-allowed': cart.items.length === 0}">
                        Confirm & Pay
                        <span class="text-[10px] opacity-60 bg-white/20 px-1.5 py-0.5 rounded ml-2">F4</span>
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
import { useSessionStore } from './stores/session';
import { startSyncWorker } from './services/sync-worker';
import { syncOrders, syncMasterData } from './services/sync-service';
import { db } from './db/pos-db';
import OpenSessionModal from './components/OpenSessionModal.vue';
import PaymentModal from './components/PaymentModal.vue';
import CloseSessionModal from './components/CloseSessionModal.vue';
import CustomerSelector from './components/CustomerSelector.vue';
import OrderHistoryPanel from './components/OrderHistoryPanel.vue';
import { useReceipt } from './composables/useReceipt';
import { useBarcodeScanner } from './composables/useBarcodeScanner';
import { useKeyboardShortcuts } from './composables/useKeyboardShortcuts';
import ScanToast from './components/ScanToast.vue';
import DiscountPopover from './components/DiscountPopover.vue';
import './echo.js';

const connectivity = useConnectivityStore();
const cart = useCartStore();
const productsStore = useProductsStore();
const sessionStore = useSessionStore();

const currentCurrency = computed(() => sessionStore.currencyCode);
const showPaymentModal = ref(false);
const showCloseSessionModal = ref(false);
const orderSuccess = ref(null);
const syncProgress = ref('');

const discountModal = ref({ visible: false, item: null });
const showOrderDiscountInput = ref(false);
const orderDiscountInput = ref(0);

// Barcode scanner
const scanToast = ref({ visible: false, type: 'success', message: '' });

const showScanFeedback = (type, message) => {
    scanToast.value = { visible: true, type, message };
    setTimeout(() => { scanToast.value.visible = false; }, type === 'success' ? 1500 : 2500);
};

const handleBarcodeScan = (barcode) => {
    const product = productsStore.products.find(
        p => p.sku && p.sku.toLowerCase() === barcode.toLowerCase()
    );
    
    if (product) {
        cart.addItem(product);
        showScanFeedback('success', `"${product.name}" added to cart`);
    } else {
        showScanFeedback('error', `Product not found: ${barcode}`);
    }
};

const { pauseScanner, resumeScanner } = useBarcodeScanner(handleBarcodeScan);

// Keyboard shortcuts
const searchInputRef = ref(null);

useKeyboardShortcuts({
    focusSearch: () => {
        searchInputRef.value?.focus();
    },
    openPayment: () => {
        if (cart.items.length > 0 && !showPaymentModal.value) {
            showPaymentModal.value = true;
        }
    },
    clearCart: () => {
        if (cart.items.length > 0) {
            cart.clearCart();
        }
    },
    toggleOrderHistory: () => {
        showOrderHistory.value = !showOrderHistory.value;
    },
    closeSession: () => {
        if (sessionStore.hasActiveSession) {
            showCloseSessionModal.value = true;
        }
    },
    closeModal: () => {
        if (orderSuccess.value) {
            orderSuccess.value = null;
        } else if (showPaymentModal.value) {
            showPaymentModal.value = false;
        } else if (showCloseSessionModal.value) {
            showCloseSessionModal.value = false;
        } else if (showOrderHistory.value) {
            showOrderHistory.value = false;
        }
    },
    incrementLastItem: () => {
        const lastItem = cart.items[cart.items.length - 1];
        if (lastItem) cart.addItem(lastItem);
    },
    decrementLastItem: () => {
        const lastItem = cart.items[cart.items.length - 1];
        if (lastItem) cart.updateQuantity(lastItem.id, lastItem.quantity - 1);
    },
    removeLastItem: () => {
        const lastItem = cart.items[cart.items.length - 1];
        if (lastItem) cart.removeItem(lastItem.id);
    },
});

// Watch modals to pause/resume scanner
watch([showPaymentModal, showCloseSessionModal], ([pay, close]) => {
    if (pay || close) {
        pauseScanner();
    } else {
        resumeScanner();
    }
});

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
             // 1. Sync master data (profiles, settings, products, etc.)
             try {
                 await syncMasterData((progress) => {
                     syncProgress.value = `Downloading Catalog: ${progress.totalItemsSynced} items...`;
                 });
             } catch (syncError) {
                 console.error('Master data sync failed', syncError);
                 sessionStore.error = 'Failed to sync terminal data. Using local cache if available.';
             } finally {
                 syncProgress.value = '';
             }

             // 2. Check session status
             await sessionStore.checkCurrentSession();

             // 3. Load data from IndexedDB into stores
             await productsStore.loadFromDb();
             if (!sessionStore.hasActiveSession) {
                 await sessionStore.loadProfiles();
             }
        } else {
             // Offline mode
             await sessionStore.checkCurrentSession();
             await productsStore.loadFromDb();
        }
        
        // Load currency from DB (synced)
        await sessionStore.loadCurrency();

        // If no active session, make sure profiles are loaded
        if (!sessionStore.hasActiveSession) {
            await sessionStore.loadProfiles();
        }
    } catch(e) {
        console.error('Initial load failed', e);
        // Ensure at least loading is false
        productsStore.loading = false;
    }

    // Load taxes
    await cart.loadTaxes();

    // Real-time stock updates
    if (window.Echo) {
        window.Echo.channel('products')
            .listen('.ProductStockUpdated', (e) => {
                productsStore.updateProductStock(e.productId, e.availableQuantity);
            });
    }
});

const showOrderHistory = ref(false);

const { printReceipt } = useReceipt();

const printLastReceipt = async () => {
    if (orderSuccess.value?.orderId) {
        await printReceipt(orderSuccess.value.orderId);
    }
};

const handleCloseSession = () => {
    showCloseSessionModal.value = true;
};

const onSessionClosed = (summary) => {
    showCloseSessionModal.value = false;
    // You could show a final summary report here if desired
};

const openItemDiscount = (item) => {
    discountModal.value = { visible: true, item };
};

const applyItemDiscount = ({ type, value }) => {
    if (discountModal.value.item) {
        cart.setItemDiscount(discountModal.value.item.id, type, value);
    }
    discountModal.value.visible = false;
};

const applyOrderDiscount = () => {
    cart.setOrderDiscount('percentage', Number(orderDiscountInput.value || 0));
    showOrderDiscountInput.value = false;
};

const clearOrderDiscount = () => {
    cart.clearOrderDiscount();
    orderDiscountInput.value = 0;
    showOrderDiscountInput.value = false;
};

// ... existing code ...

// Alias for refresh button
const loadProducts = async () => {
    await productsStore.syncAndReload((progress) => {
        syncProgress.value = `Downloading Catalog: ${progress.totalItemsSynced} items...`;
    });
    syncProgress.value = '';
};

const filterCategory = (id) => {
    productsStore.selectCategory(id);
};

const addToCart = (product) => {
    cart.addItem(product);
};

const handlePaymentComplete = async (paymentData) => {
    try {
        // 1. Generate UUID & Order Number
        const orderUuid = crypto.randomUUID();
        const lastOrderNumSetting = await db.settings.get('last_order_number');
        let sequence = 1;
        if (lastOrderNumSetting) {
            sequence = (parseInt(lastOrderNumSetting.value) || 0) + 1;
        }
        const orderNumber = `POS-${sessionStore.sessionId}-${String(sequence).padStart(4, '0')}`;
        
        // 2. Get Currency
        const currencySetting = await db.settings.get('company_currency');
        const currencyId = currencySetting?.value?.id || 1; 
        
        // 3. Prepare Order Data
        // IMPORTANT: Calculate totals from itemsWithTax to ensure consistency
        const items = cart.itemsWithTax;
        
        const order = {
            uuid: orderUuid,
            order_number: orderNumber,
            status: 'paid',
            ordered_at: new Date().toISOString(),
            total_amount: cart.total,
            total_tax: cart.tax,
            discount_amount: cart.totalDiscount,
            notes: '',
            customer_id: cart.currentCustomer?.id || null,
            currency_id: currencyId,
            pos_session_id: sessionStore.sessionId,
            sector_data: [],
            sync_status: 'pending',
            payment_method: paymentData.method,
            amount_tendered: paymentData.amount_tendered,
            change_given: paymentData.change_given,
        };
        
        const lines = items.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            discount_amount: item.discount_amount,
            tax_amount: item.tax_amount,
            total_amount: item.total_amount,
            metadata: [],
        }));

        // 4. Save to DB Transaction
        const orderId = await db.transaction('rw', db.orders, db.order_lines, db.settings, async () => {
            const id = await db.orders.add(order);
            const linesWithOrderId = lines.map(l => ({ ...l, order_id: id }));
            await db.order_lines.bulkAdd(linesWithOrderId);
            await db.settings.put({ key: 'last_order_number', value: sequence });
            return id;
        });
        
        // 5. Success UI
        showPaymentModal.value = false;
        orderSuccess.value = {
            orderId: orderId,
            orderNumber: orderNumber,
            total: order.total_amount,
            change: order.change_given,
            method: order.payment_method
        };
        
        // 6. Clear Cart & Sync
        await cart.clearCart();
        
        // Auto-dismiss success after 5 seconds if not clicked
        setTimeout(() => {
             if (orderSuccess.value && orderSuccess.value.orderNumber === orderNumber) {
                 orderSuccess.value = null;
             }
        }, 5000);

        if (connectivity.isOnline) {
            syncOrders();
        }

    } catch (e) {
        console.error('Failed to create order', e);
        alert('Failed to process order. Please try again.');
    }
};

const dismissSuccess = () => {
    orderSuccess.value = null;
};

const searchQuery = computed({
    get: () => productsStore.searchQuery,
    set: (val) => productsStore.setSearchQuery(val)
});

// Helper for money formatting
const formatMoney = (amount) => {
    if (amount === undefined || amount === null) return '0.00';
    // Amount is in minor units (integer). Divide by decimalFactor.
    const val = Number(amount) / sessionStore.decimalFactor;
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: sessionStore.currencyCode,
        minimumFractionDigits: sessionStore.decimalPlaces,
        maximumFractionDigits: sessionStore.decimalPlaces
    }).format(val);
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
