import axios from 'axios';
import { db } from '../db/pos-db';

const api = axios.create({
    baseURL: '/api/pos',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

export const syncMasterData = async () => {
    try {
        const settings = await db.settings.get('last_master_sync');
        const since = settings ? settings.value : null;
        
        const response = await api.get('/sync/master-data', { params: { since } });
        const data = response.data; // { products: [], categories: [], ... }

        // Needs to map Resource fields to DB schema if different?
        // Assuming Backend Resource returns fields matching DB schema expectations
        // or we handle mapping here.
        // DB schema: products: '++id, name, sku, category_id'
        // Resource: id, name, sku, unit_price, ...
        // Dexie ignores extra fields in object unless defined in store? 
        // No, Dexie stores the object. The schema defines indices.
        
        await db.transaction('rw', db.products, db.categories, db.customers, db.settings, async () => {
            if (data.products && data.products.length) await db.products.bulkPut(data.products);
            if (data.categories && data.categories.length) await db.categories.bulkPut(data.categories);
            if (data.customers && data.customers.length) await db.customers.bulkPut(data.customers);
            if (data.profiles && data.profiles.length) {
                // profiles store? missing in Step 11 schema. 
                // Need to add 'profiles' store to pos-db.js or ignore.
                // User said "PosProfile settings". Maybe store in settings or separate store.
                // "pos_profiles — Terminal configuration" in DB schema.
                // IndexDB should probably store profiles too.
            }
            
            await db.settings.put({ key: 'last_master_sync', value: data.timestamp });
        });
        
        return data;
    } catch (error) {
        console.error('Master Data Sync Failed:', error);
        throw error;
    }
};

export const syncOrders = async () => {
    try {
        const pending = await db.orders.where('sync_status').equals('pending').toArray();
        if (!pending.length) return { synced: [], failed: [] };
        
        // Hydrate order lines
        const ordersPayload = await Promise.all(pending.map(async (order) => {
            // Need to join lines
            // However, relation is local ID based? 
            // order_lines.order_id matches orders.id (auto-increment).
            // DTO expects UUID or structure?
            // Backend creates PosOrder with UUID.
            // PosOrderLine in backend links to PosOrder.
            
            const lines = await db.order_lines.where('order_id').equals(order.id).toArray();
            
            return {
                uuid: order.uuid,
                order_number: order.order_number,
                status: order.status,
                ordered_at: order.ordered_at, // Ensure format
                total_amount: String(order.total_amount),
                total_tax: String(order.total_tax),
                notes: order.notes,
                customer_id: order.customer_id,
                currency_id: order.currency_id,
                pos_session_id: order.pos_session_id, // Ensure this exists in local DB
                sector_data: order.sector_data || [],
                lines: lines.map(l => ({
                    product_id: l.product_id,
                    quantity: Number(l.quantity),
                    unit_price: String(l.unit_price),
                    tax_amount: String(l.tax_amount),
                    total_amount: String(l.total_amount),
                    metadata: l.metadata || []
                }))
            };
        }));
        
        const response = await api.post('/sync/orders', { orders: ordersPayload });
        const result = response.data; // { synced: [], failed: [] }
        
        if (result.synced && result.synced.length) {
            // Update sync_status
            await db.orders.where('uuid').anyOf(result.synced).modify({ sync_status: 'synced' });
        }
        
        return result;
    } catch (error) {
        console.error('Order Sync Failed:', error);
        throw error;
    }
};

export const openSession = async (profileId, openingCash) => {
    const response = await api.post('/sessions/open', { pos_profile_id: profileId, opening_cash: openingCash });
    return response.data;
};

export const closeSession = async (sessionId, closingCash) => {
    const response = await api.post(`/sessions/${sessionId}/close`, { closing_cash: closingCash });
    return response.data;
};
