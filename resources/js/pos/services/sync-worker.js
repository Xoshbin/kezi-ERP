import { syncOrders, syncMasterData } from './sync-service';
import { useConnectivityStore } from '../stores/connectivity';

export const startSyncWorker = (intervalMs = 30000) => {
    const connectivityStore = useConnectivityStore();
    
    // Initial sync
    if (connectivityStore.isOnline) {
        syncMasterData().catch(e => console.error('Initial Master Sync Failed', e));
        syncOrders().catch(e => console.error('Initial Order Sync Failed', e));
    }
    
    // Periodic sync
    setInterval(async () => {
        if (!connectivityStore.isOnline || connectivityStore.isSyncing) return;
        
        try {
            connectivityStore.setSyncing(true);
            await syncOrders();
            // Assuming master data sync is less frequent or manual? Or same interval?
            // "Periodic sync using setInterval... On reconnect, trigger an immediate full sync."
            // Let's sync master data less frequently or same.
            // For now, sync orders every 30s. Master data might be heavy.
            // Let's sync Master Data here too for "incremental" updates.
            await syncMasterData();
            
            connectivityStore.updateLastSync();
        } catch (e) {
            console.error('Background Sync Failed', e);
        } finally {
            connectivityStore.setSyncing(false);
        }
    }, intervalMs);
    
    // Listen for online event to trigger immediate sync
    window.addEventListener('online', () => {
        console.log('Back online, triggering sync...');
        syncMasterData();
        syncOrders();
    });
};
