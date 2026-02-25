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

export const syncMasterData = async (onProgress = null) => {
    try {
        const settings = await db.settings.get('last_master_sync');
        const since = settings ? settings.value : null;
        
        let page = 1;
        const limit = 500;
        let hasMore = true;
        let lastTimestamp = null;
        let totalItemsSynced = 0;

        // If this is a full sync (no since timestamp), clear current tables to remove stale data
        if (!since) {
            await db.transaction('rw', db.products, db.categories, db.customers, db.taxes, db.profiles, async () => {
                await Promise.all([
                    db.products.clear(),
                    db.categories.clear(),
                    db.customers.clear(),
                    db.taxes.clear(),
                    db.profiles.clear()
                ]);
            });
        }

        while (hasMore) {
            const response = await api.get('/sync/master-data', { params: { since, page, limit } });
            const data = response.data; // { products: [], categories: [], ... }
            
            await db.transaction('rw', db.products, db.categories, db.customers, db.taxes, db.profiles, db.settings, async () => {
                if (data.products && data.products.length) {
                    await db.products.bulkPut(data.products);
                    totalItemsSynced += data.products.length;
                }
                if (data.categories && data.categories.length) await db.categories.bulkPut(data.categories);
                if (data.customers && data.customers.length) {
                    await db.customers.bulkPut(data.customers);
                    totalItemsSynced += data.customers.length;
                }
                if (data.taxes && data.taxes.length) await db.taxes.bulkPut(data.taxes);
                if (data.profiles && data.profiles.length) await db.profiles.bulkPut(data.profiles);
                
                if (data.company_currency) {
                    await db.settings.put({ key: 'company_currency', value: data.company_currency });
                }

                if (data.timestamp) {
                    lastTimestamp = data.timestamp;
                }
            });
            
            hasMore = data.has_more;
            
            if (onProgress) {
                onProgress({ page, limit, hasMore, totalItemsSynced });
            }
            
            page++;
        }

        if (lastTimestamp) {
            await db.transaction('rw', db.settings, async () => {
                await db.settings.put({ key: 'last_master_sync', value: lastTimestamp });
            });
        }
        
        return { success: true };
    } catch (error) {
        console.error('Master Data Sync Failed:', error);
        throw error;
    }
};

/**
 * Phase 3 – Throttled Batch Order Sync.
 *
 * Splits pending orders into chunks of BATCH_SIZE (default 50) and uploads
 * them one batch at a time with a BATCH_PAUSE_MS (500 ms) delay in between.
 * If an individual batch request fails (network error, 5xx, etc.) the orders
 * in that batch are isolated into the `failed` list and the loop continues
 * with the remaining batches — the entire queue never stalls because of one
 * bad chunk.
 */
const BATCH_SIZE = 50;
const BATCH_PAUSE_MS = 500;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const chunkArray = (array, size) => {
    const chunks = [];
    for (let i = 0; i < array.length; i += size) {
        chunks.push(array.slice(i, i + size));
    }
    return chunks;
};

export const syncOrders = async () => {
    const pending = await db.orders.where('sync_status').equals('pending').toArray();
    if (!pending.length) return { synced: [], failed: [] };

    // Hydrate all order lines up front so we can split into batches afterwards.
    const hydratedOrders = await Promise.all(
        pending.map(async (order) => {
            const lines = await db.order_lines.where('order_id').equals(order.id).toArray();
            const pmts = await db.order_payments.where('order_id').equals(order.id).toArray();

            return {
                uuid: order.uuid,
                order_number: order.order_number,
                status: order.status,
                ordered_at: order.ordered_at,
                total_amount: String(order.total_amount),
                total_tax: String(order.total_tax),
                discount_amount: String(order.discount_amount || 0),
                notes: order.notes,
                customer_id: order.customer_id,
                currency_id: order.currency_id,
                pos_session_id: order.pos_session_id,
                sector_data: order.sector_data || [],
                lines: lines.map((l) => ({
                    product_id: l.product_id,
                    quantity: Number(l.quantity),
                    unit_price: String(l.unit_price),
                    discount_amount: String(l.discount_amount || 0),
                    tax_amount: String(l.tax_amount),
                    total_amount: String(l.total_amount),
                    metadata: l.metadata || [],
                })),
                // Split payments (new field — backend is backward-compatible)
                payments: pmts.map((p) => ({
                    method: p.method,
                    amount: Number(p.amount),
                    amount_tendered: p.amount_tendered ?? null,
                    change_given: Number(p.change_given ?? 0),
                })),
            };
        })
    );

    const batches = chunkArray(hydratedOrders, BATCH_SIZE);
    const allSynced = [];
    const allFailed = [];

    for (let batchIndex = 0; batchIndex < batches.length; batchIndex++) {
        const batch = batches[batchIndex];

        try {
            const response = await api.post('/sync/orders', { orders: batch });
            const result = response.data; // { synced: [], failed: [] }

            if (result.synced && result.synced.length) {
                // Mark successfully synced orders in IndexedDB.
                await db.orders.where('uuid').anyOf(result.synced).modify({ sync_status: 'synced' });
                allSynced.push(...result.synced);
            }

            if (result.failed && result.failed.length) {
                // The backend processed the batch but some individual orders failed
                // (e.g. duplicate UUID, invalid session). Collect them so callers
                // can surface them to the user without blocking the rest.
                allFailed.push(...result.failed);
            }
        } catch (error) {
            // The entire batch request failed (network outage, 429, 502, …).
            // Isolate this batch and keep going — don't block subsequent batches.
            console.error(`Order sync batch ${batchIndex + 1}/${batches.length} failed:`, error);
            const batchUuids = batch.map((o) => o.uuid);
            allFailed.push(
                ...batchUuids.map((uuid) => ({
                    uuid,
                    error: error?.response?.data?.message || error.message || 'Network error',
                }))
            );
        }

        // Pause between batches to avoid overwhelming the server.
        if (batchIndex < batches.length - 1) {
            await sleep(BATCH_PAUSE_MS);
        }
    }

    return { synced: allSynced, failed: allFailed };
};

export const getCurrentSession = async () => {
    try {
        const response = await api.get('/sessions/current');
        return response.data;
    } catch (error) {
        if (error.response && error.response.status === 404) {
            return null;
        }
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

// POS Returns API
export const quickSearchOrders = async (query, sessionId = null) => {
    const response = await api.get('/orders/quick-search', { 
        params: { q: query, session_id: sessionId } 
    });
    return response.data;
};

export const getOrderDetails = async (orderId) => {
    const response = await api.get(`/orders/${orderId}/details`);
    return response.data;
};

export const checkReturnEligibility = async (orderId) => {
    const response = await api.get(`/orders/${orderId}/return-eligibility`);
    return response.data;
};

export const createReturn = async (payload) => {
    const response = await api.post('/returns', payload);
    return response.data;
};

export const submitReturn = async (returnId) => {
    const response = await api.post(`/returns/${returnId}/submit`);
    return response.data;
};

export const approveReturn = async (returnId) => {
    const response = await api.post(`/returns/${returnId}/approve`);
    return response.data;
};

export const rejectReturn = async (returnId, reason) => {
    const response = await api.post(`/returns/${returnId}/reject`, { reason });
    return response.data;
};

export const processReturn = async (returnId) => {
    const response = await api.post(`/returns/${returnId}/process`);
    return response.data;
};

/**
 * 6a — Manager PIN verification.
 * Sends the manager's PIN to the backend for validation and, on success,
 * immediately approves the return.
 */
export const verifyManagerPin = async (returnId, pin) => {
    const response = await api.post(`/returns/${returnId}/verify-pin`, { pin });
    return response.data;
};

/**
 * 6d — Offline search cache.
 * Downloads the last N orders for the current company and stores them in
 * IndexedDB so that receipt lookups work without an internet connection.
 */
export const cacheRecentOrders = async (limit = 50) => {
    try {
        const response = await api.post('/orders/search', {
            per_page: limit,
            status: null, // all statuses except cancelled (handled server-side)
        });
        const orders = response.data?.data ?? [];
        if (orders.length) {
            await db.transaction('rw', db.recent_orders, async () => {
                await db.recent_orders.clear();
                await db.recent_orders.bulkPut(orders);
            });
        }
        return orders.length;
    } catch {
        // Silently fail — this is an optimistic cache refresh
        return 0;
    }
};

/**
 * 6d — Offline receipt search.
 * Searches the local IndexedDB recent_orders table. Only used as a fallback
 * when the device is offline.
 */
export const searchOrdersOffline = async (searchTerm, activeFilter = null) => {
    let orders = await db.recent_orders.toArray();

    // Date filter
    if (activeFilter && activeFilter !== 'all') {
        const now = new Date();
        let cutoff;
        if (activeFilter === 'today') {
            cutoff = new Date(now.getFullYear(), now.getMonth(), now.getDate()).toISOString();
        } else if (activeFilter === 'yesterday') {
            const y = new Date(now);
            y.setDate(y.getDate() - 1);
            cutoff = new Date(y.getFullYear(), y.getMonth(), y.getDate()).toISOString();
            const end = new Date(now.getFullYear(), now.getMonth(), now.getDate()).toISOString();
            orders = orders.filter(o => o.ordered_at >= cutoff && o.ordered_at < end);
        } else if (activeFilter === 'week') {
            const w = new Date(now);
            w.setDate(w.getDate() - 7);
            cutoff = w.toISOString();
        }
        if (activeFilter === 'today' || activeFilter === 'week') {
            orders = orders.filter(o => o.ordered_at >= cutoff);
        }
    }

    // Text search
    if (searchTerm && searchTerm.length >= 1) {
        const q = searchTerm.toLowerCase();
        orders = orders.filter(o =>
            (o.order_number && o.order_number.toLowerCase().includes(q)) ||
            (o.customer?.name && o.customer.name.toLowerCase().includes(q))
        );
    }

    return orders.slice(0, 20);
};

export const syncReturns = async () => {
    try {
        const pending = await db.returns.where('sync_status').equals('pending').toArray();
        if (!pending.length) return { synced: [], failed: [] };

        const synced = [];
        const failed = [];

        for (const posReturn of pending) {
            try {
                const lines = await db.return_lines.where('return_id').equals(posReturn.id).toArray();
                
                const payload = {
                    ...posReturn,
                    lines: lines.map(l => ({
                        ...l,
                        refund_amount: l.quantity_returned * l.unit_price,
                        restocking_fee_line: 0 // Placeholder or calculated
                    }))
                };

                const response = await api.post('/returns', payload);
                const returnData = response.data;

                // 2. Submit the return
                const submittedReturn = await submitReturn(returnData.id);

                // 3. Update local DB
                await db.transaction('rw', db.returns, async () => {
                    await db.returns.update(posReturn.id, {
                        uuid: submittedReturn.uuid,
                        status: submittedReturn.status,
                        sync_status: 'synced'
                    });
                });

                synced.push(posReturn.uuid);
            } catch (e) {
                console.error(`Failed to sync return ${posReturn.uuid}`, e);
                failed.push({ uuid: posReturn.uuid, error: e.message });
            }
        }

        return { synced, failed };
    } catch (error) {
        console.error('Returns Sync Failed:', error);
        throw error;
    }
};

export { api };
