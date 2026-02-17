import Dexie from 'dexie';

export const db = new Dexie('KeziPosDatabase');

db.version(1).stores({
    products: '++id, name, sku, category_id',
    categories: '++id, name',
    orders: '++id, uuid, status, sync_status',
    order_lines: '++id, order_id, product_id',
    customers: '++id, name, email',
    settings: 'key'
});

export const getMasterData = async () => {
    // Helper to get master data from IndexedDB
    return {
        products: await db.products.toArray(),
        categories: await db.categories.toArray(),
        customers: await db.customers.toArray(),
    };
};
