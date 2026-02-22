import Dexie from 'dexie';

export const db = new Dexie('KeziPosDatabase');

db.version(5).stores({
    products: 'id, name, sku, category_id, is_active',
    categories: 'id, name',
    orders: '++id, uuid, status, sync_status',
    order_lines: '++id, order_id, product_id',
    customers: 'id, name, email',
    taxes: 'id, name, amount',
    settings: 'key',
    profiles: 'id, name'
});

export const getMasterData = async () => {
    // Helper to get master data from IndexedDB
    return {
        products: await db.products.toArray(),
        categories: await db.categories.toArray(),
        customers: await db.customers.toArray(),
    };
};
