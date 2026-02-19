import { defineStore } from 'pinia';
import { db } from '../db/pos-db';

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
        currentCustomer: null,
        profile: null,
        posSessionId: null,
        taxes: [],
        orderDiscount: { type: 'percentage', value: 0 },
    }),

    getters: {
        totalQuantity: (state) => state.items.reduce((sum, item) => sum + item.quantity, 0),

        // subtotal = price × qty (BEFORE discounts)
        subtotal: (state) => state.items.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0),

        // Total line-level discount amount
        lineDiscountTotal: (state) => {
            return state.items.reduce((total, item) => {
                const lineSubtotal = item.unit_price * item.quantity;
                if (item.discount_type === 'percentage') {
                    return total + Math.round(lineSubtotal * (item.discount_value / 100));
                } else if (item.discount_type === 'fixed') {
                    return total + Math.min(item.discount_value, lineSubtotal); // Can't discount more than line total
                }
                return total;
            }, 0);
        },

        // Order-level discount amount (applied to subtotal AFTER line discounts)
        orderDiscountAmount: (state) => {
            const afterLineDiscounts = state.items.reduce((sum, item) => {
                const lineSubtotal = item.unit_price * item.quantity;
                let lineDiscount = 0;
                if (item.discount_type === 'percentage') {
                    lineDiscount = Math.round(lineSubtotal * (item.discount_value / 100));
                } else if (item.discount_type === 'fixed') {
                    lineDiscount = Math.min(item.discount_value, lineSubtotal);
                }
                return sum + (lineSubtotal - lineDiscount);
            }, 0);

            if (state.orderDiscount.type === 'percentage' && state.orderDiscount.value > 0) {
                return Math.round(afterLineDiscounts * (state.orderDiscount.value / 100));
            }
            return 0;
        },

        // Combined total discount
        totalDiscount() {
            return this.lineDiscountTotal + this.orderDiscountAmount;
        },

        // Tax computed on discounted amounts
        tax: (state) => {
            return state.items.reduce((totalTax, item) => {
                const lineSubtotal = item.unit_price * item.quantity;

                // Apply line discount
                let lineDiscount = 0;
                if (item.discount_type === 'percentage') {
                    lineDiscount = Math.round(lineSubtotal * (item.discount_value / 100));
                } else if (item.discount_type === 'fixed') {
                    lineDiscount = Math.min(item.discount_value, lineSubtotal);
                }
                const taxableBase = lineSubtotal - lineDiscount;

                // Apply order discount proportionally
                // (If order has 10% discount, each line's taxable base is reduced by 10%)
                let orderDiscountRatio = 0;
                if (state.orderDiscount.type === 'percentage' && state.orderDiscount.value > 0) {
                    orderDiscountRatio = state.orderDiscount.value / 100;
                }
                const finalTaxableBase = Math.round(taxableBase * (1 - orderDiscountRatio));

                // Calculate tax on final taxable base
                const itemTaxRate = (item.tax_ids || []).reduce((rateSum, taxId) => {
                    const tax = state.taxes.find(t => t.id === taxId);
                    return rateSum + (tax ? parseFloat(tax.rate) : 0);
                }, 0);

                return totalTax + Math.round(finalTaxableBase * itemTaxRate);
            }, 0);
        },

        // Total = subtotal - totalDiscount + tax
        total() {
            return this.subtotal - this.totalDiscount + this.tax;
        },

        // itemsWithTax includes discount info for order sync
        itemsWithTax: (state) => {
            const orderDiscountRatio = (state.orderDiscount.type === 'percentage' && state.orderDiscount.value > 0)
                ? state.orderDiscount.value / 100
                : 0;

            return state.items.map(item => {
                const lineSubtotal = item.unit_price * item.quantity;

                let lineDiscount = 0;
                if (item.discount_type === 'percentage') {
                    lineDiscount = Math.round(lineSubtotal * (item.discount_value / 100));
                } else if (item.discount_type === 'fixed') {
                    lineDiscount = Math.min(item.discount_value, lineSubtotal);
                }

                const afterLineDiscount = lineSubtotal - lineDiscount;
                const orderDiscountPortion = Math.round(afterLineDiscount * orderDiscountRatio);
                const totalDiscountOnLine = lineDiscount + orderDiscountPortion;
                const taxableBase = afterLineDiscount - orderDiscountPortion;

                const itemTaxRate = (item.tax_ids || []).reduce((rateSum, taxId) => {
                    const tax = state.taxes.find(t => t.id === taxId);
                    return rateSum + (tax ? parseFloat(tax.rate) : 0);
                }, 0);
                const taxAmount = Math.round(taxableBase * itemTaxRate);

                return {
                    ...item,
                    discount_amount: totalDiscountOnLine,
                    tax_amount: taxAmount,
                    total_amount: taxableBase + taxAmount,
                };
            });
        },
    },

    actions: {
        async loadTaxes() {
            try {
                this.taxes = await db.taxes.toArray();
            } catch (e) {
                console.error('Failed to load taxes:', e);
            }
        },

        setItemDiscount(productId, discountType, discountValue) {
            const item = this.items.find(i => i.id === productId);
            if (item) {
                item.discount_type = discountType; // 'percentage' | 'fixed' | null
                item.discount_value = discountValue; // percentage 0-100 or minor units
            }
        },

        clearItemDiscount(productId) {
            const item = this.items.find(i => i.id === productId);
            if (item) {
                item.discount_type = null;
                item.discount_value = 0;
            }
        },

        setOrderDiscount(type, value) {
            this.orderDiscount = { type, value };
        },

        clearOrderDiscount() {
            this.orderDiscount = { type: 'percentage', value: 0 };
        },

        addItem(product) {
            const existing = this.items.find(item => item.id === product.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({
                    ...product,
                    quantity: 1,
                    discount_type: null,
                    discount_value: 0,
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

        setCustomer(customer) {
            this.currentCustomer = customer;
        },

        async clearCart() {
            this.items = [];
            this.currentCustomer = null;
            this.orderDiscount = { type: 'percentage', value: 0 };
        },
    }
});
