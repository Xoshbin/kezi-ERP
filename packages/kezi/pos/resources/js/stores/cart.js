import { defineStore } from 'pinia';
import { db } from '../db/pos-db';

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        currentCustomer: null,
        profile: null,
        posSessionId: null,
        taxes: [],
    }),
    
    getters: {
        totalQuantity: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),
        subtotal: (state) => state.items.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0),
        
        tax: (state) => {
            return state.items.reduce((totalTax, item) => {
                const itemTaxRate = (item.tax_ids || []).reduce((rateSum, taxId) => {
                    const tax = state.taxes.find(t => t.id === taxId);
                    return rateSum + (tax ? parseFloat(tax.rate) : 0);
                }, 0);
                
                // Tax = price * quantity * rate
                // Round to integer (minor units)
                const lineTax = Math.round(item.unit_price * item.quantity * itemTaxRate);
                return totalTax + lineTax;
            }, 0);
        },
        
        total: (getters) => getters.subtotal + getters.tax,

        // Helper to get items with calculated tax amounts for order submission
        itemsWithTax: (state) => {
            return state.items.map(item => {
                 const itemTaxRate = (item.tax_ids || []).reduce((rateSum, taxId) => {
                    const tax = state.taxes.find(t => t.id === taxId);
                    return rateSum + (tax ? parseFloat(tax.rate) : 0);
                }, 0);
                
                const taxAmount = Math.round(item.unit_price * item.quantity * itemTaxRate);
                
                return {
                    ...item,
                    tax_amount: taxAmount,
                    total_amount: (item.unit_price * item.quantity) + taxAmount
                };
            });
        }
    },
    
    actions: {
        async loadTaxes() {
            try {
                this.taxes = await db.taxes.toArray();
            } catch (e) {
                console.error('Failed to load taxes:', e);
            }
        },

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
