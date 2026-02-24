import Dexie from 'dexie';

export const db = new Dexie('KeziPosDatabase');

db.version(6).stores({
    products: 'id, name, sku, category_id, is_active',
    categories: 'id, name',
    orders: '++id, uuid, status, sync_status',
    order_lines: '++id, order_id, product_id',
    customers: 'id, name, email',
    taxes: 'id, name, amount',
    settings: 'key',
    profiles: 'id, name',
    returns: '++id, uuid, original_order_id, status, sync_status',
    return_lines: '++id, return_id, product_id'
});

// v7 — add recent_orders table for offline receipt search cache (Phase 6d)
db.version(7).stores({
    products: 'id, name, sku, category_id, is_active',
    categories: 'id, name',
    orders: '++id, uuid, status, sync_status',
    order_lines: '++id, order_id, product_id',
    customers: 'id, name, email',
    taxes: 'id, name, amount',
    settings: 'key',
    profiles: 'id, name',
    returns: '++id, uuid, original_order_id, status, sync_status',
    return_lines: '++id, return_id, product_id',
    recent_orders: 'id, order_number, ordered_at, status',
});

export const getMasterData = async () => {
    // Helper to get master data from IndexedDB
    return {
        products: await db.products.toArray(),
        categories: await db.categories.toArray(),
        customers: await db.customers.toArray(),
    };
};
