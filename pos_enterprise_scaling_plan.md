# Universal POS: Enterprise Scaling & Readiness Plan

This document outlines the architectural changes required to transition the Universal POS from a strong foundation into a robust, high-availability system capable of handling massive catalogs, high-concurrency environments (like supermarkets and large restaurants), and extended offline periods.

## Phase 1: Overcoming the Memory Limit (Catalog Pagination)
Currently, a fresh terminal downloads the entire catalog in a single `/api/pos/sync/master-data` request. For environments with 50,000+ SKUs, this will crash the PHP worker (`Allowed memory size exhausted`) and time out the terminal.

### Action Items:
1. **Backend Pagination**: 
   - Modify `MasterDataSyncController` and `PosSyncService` to support chunked responses.
   - Use DB cursors or simple offset pagination for the `products` and `customers` arrays.
2. **Frontend Orchestration**: 
   - Update `syncMasterData()` in `sync-service.js` to loop and fetch data in pages (`/api/pos/sync/master-data?page=1`).
   - Store arrays in IndexedDB chunk by chunk.
3. **UI Feedback**: 
   - Display a loading progress bar during the initial sync: *"Downloading Catalog: 15,000 / 50,000 items..."*

## Phase 2: Resolving Concurrency Overselling (WebSockets & Reverb)
Polling every 30 seconds leaves a 30-second window where two cashiers can sell the exact same final item in stock. We need real-time awareness.

### Action Items:
1. **Infrastructure**: 
   - Install and configure **Laravel Reverb** (or Pusher) for WebSockets.
2. **Backend Events**: 
   - Create a `ProductStockUpdated` broadcast event.
   - Dispatch this event from the `StockQuantObserver` whenever inventory is altered (e.g., replenished by a Purchase Order or depleted by a POS order).
3. **Frontend Listeners**: 
   - Install Laravel Echo in the Vue frontend.
   - Listen to the `ProductStockUpdated` channel.
   - When received, instantly update the `available_quantity` of that specific product in the Pinia store and IndexedDB, bypassing the heavy 30-second polling cycle.

## Phase 3: Surviving Widespread Outages (Throttled Batch Syncing)
If the internet goes down for the entire day, a terminal might accumulate thousands of orders in its IndexedDB. When connection is restored, dumping a massive JSON array of 5,000 orders against the API will trigger rate limits, NGINX payload limits, or database deadlocks.

### Action Items:
1. **Batch Queuing in JS**: 
   - Modify `syncOrders()` in `sync-service.js` to process a maximum of `50` orders per payload.
2. **Staggered Uploads**: 
   - Implement a mechanism where the JS worker uploads 50 orders, awaits success, updates their local `sync_status`, pauses briefly (e.g., 500ms), and proceeds to the next 50.
3. **Resiliency**: 
   - If a specific batch fails (e.g., due to a malformed order), isolate it and continue syncing the rest of the queue so the entire terminal doesn't stall.

## Phase 4: Ground Truth Reconciliation (Negative Inventory)
Offline-first systems inevitably experience "Negative Stock." If the terminal is offline, it will allow the sale of an item it thinks it has. When it syncs back, the warehouse might actually be empty.

### Action Items:
1. **ERP Behavior Definition**: 
   - Allow the `OrderSyncController` to successfully process the order even if stock falls below zero (because the physical item has already physically left the store with the customer).
2. **Negative Stock Flagging**: 
   - Introduce a new state or alert flag for `StockQuant` when it drops below zero.
3. **Management Dashboard**: 
   - Create a Filament view (e.g., "Inventory Discrepancies") exclusively for managers to review and reconcile negative inventory (likely due to theft, miscounts, or unaccounted deliveries).

---
*By executing these four phases, the Universal POS will be robust enough to handle enterprise-level traffic without degrading performance or causing data bottlenecks.*
