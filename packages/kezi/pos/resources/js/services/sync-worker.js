import { syncOrders, syncMasterData } from './sync-service';
import { useConnectivityStore } from '../stores/connectivity';
import { useProductsStore } from '../stores/products';

export const startSyncWorker = (intervalMs = 30000) => {
    const connectivityStore = useConnectivityStore();
    const productsStore = useProductsStore();
    
    // Initial sync - Handled by store.syncAndReload used in App.vue onMounted
    // But if worker started first? No, worker started in App.vue onMounted.
    // However, App.vue awaits store.syncAndReload().
    // So sync-worker initial sync here might duplicate.
    // I'll make sync-worker skip initial sync or let App.vue handle logic.
    // The prompt says "Initial Load: On mount, if online, await syncMasterData() completion BEFORE calling loadData()".
    // App.vue calls syncAndReload() which calls syncMasterData().
    // If I keep startSyncWorker here triggering on start, two syncs happen.
    // I'll remove lines 7-11 here to prevent duplication, letting App.vue handle initial load.
    // OR keep strict periodic sync logic.
    
    // Actually, startSyncWorker is imported to run interval.
    // Let's remove initial immediate sync from here if desired, or ensure idempotency.
    // "Initial Load: On mount, if online, await syncMasterData() completion BEFORE calling loadData()".
    // If startSyncWorker also calls it async without waiting, race condition.
    // I'll remove the immediate execution block from startSyncWorker, letting App.vue handle the first one.
    
    // Periodic sync
    setInterval(async () => {
        if (!connectivityStore.isOnline || connectivityStore.isSyncing) return;
        
        try {
            connectivityStore.setSyncing(true);
            await syncOrders();
            await syncMasterData(); // Fetch updates
            await productsStore.loadFromDb(); // Reload store from DB to show updates
            
            connectivityStore.updateLastSync();
        } catch (e) {
            console.error('Background Sync Failed', e);
        } finally {
            connectivityStore.setSyncing(false);
        }
    }, intervalMs);
    
    // Listen for online event to trigger immediate sync
    window.addEventListener('online', async () => {
        console.log('Back online, triggering sync...');
        await syncOrders();
        await syncMasterData();
        await productsStore.loadFromDb();
    });
};
