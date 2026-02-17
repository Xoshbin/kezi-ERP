import { defineStore } from 'pinia';
import { db } from '../db/pos-db';

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        currentCustomer: null,
        profile: null,
    }),
    
    getters: {
        totalQuantity: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
        subtotal: (state) => state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        tax: (getters) => getters.subtotal * 0.08,
        total: (getters) => getters.subtotal + getters.tax,
    },
    
    actions: {
        addItem(product) {
            const existing = this.items.find(item => item.id === product.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({
                    ...product,
                    quantity: 1
                });
            }
        },
        
        removeItem(productId) {
            this.items = this.items.filter(item => item.id !== productId);
        },
        
        updateQuantity(productId, quantity) {
            const item = this.items.find(item => item.id === productId);
            if (item) {
                item.quantity = Math.max(0, quantity);
                if (item.quantity === 0) {
                    this.removeItem(productId);
                }
            }
        },
        
        async clearCart() {
            this.items = [];
            this.currentCustomer = null;
        }
    }
});
