Overview
I analyzed your Laravel inventory domain across models, actions, services, observers, listeners, jobs, and migrations, and compared it to Odoo best practices. Below is a concrete gap analysis and an implementation roadmap tailored to your Laravel/Filament architecture and stated preferences.

1. Current Application Analysis
   Stock movement tracking and recording
   Core entities: Product, StockLocation, StockMove, InventoryCostLayer, StockMoveValuation.
   Creation paths:
   Purchases: VendorBillService::post creates Incoming StockMove(s) Vendor → Warehouse, status Done, but does NOT dispatch StockMoveConfirmed, so valuation jobs do not run for purchases.
   Sales: CreateStockMovesForInvoiceAction creates Outgoing moves and DOES dispatch StockMoveConfirmed to trigger COGS processing.
   Observer: StockMoveObserver logs to AuditLog only; no business logic here.
   Issue: Sales uses vendor location as “to” (should be a Customer location).
   Evidence:

CreateStockMovesForInvoiceAction.php
app/Actions/Sales
Price management and pricing strategies
Product has unit_price; no price lists/tiers/discount rules found in inventory scope.
Pricing handled primarily in sales (invoices), not inventory. No advanced pricing strategies in inventory layer.
Inventory valuation methods
Supported enum: FIFO, LIFO, AVCO, STANDARD.
InventoryValuationService:
Outgoing: COGS calculated (AVCO via product.average_cost; FIFO/LIFO via InventoryCostLayer consumption), posts a COGS JE, creates StockMoveValuation.
Incoming: Creates JE (Inventory Dr / Stock Input Cr) and StockMoveValuation, and for FIFO/LIFO creates cost layers; AVCO updates average cost and quantity.
Problem 1: VendorBill flow does not run InventoryValuationService for incoming; instead it creates a combined bill JE and separately updates product stats (average_cost and quantity) via UpdateProductInventoryStatsAction. This splits accounting/valuation logic and skips StockMoveValuation and cost layers for purchases.
Problem 2: STANDARD method present in enum but not implemented in logic branches (treated as FIFO/LIFO fallback path). Risk of incorrect behavior if set to STANDARD.
Stock level monitoring and alerts
No reorder rules, safety stock, or alerts found.
Purchase order and receiving processes
No explicit Purchase Order model; VendorBill is the source document for receipts.
VendorBillService::post:
Creates Incoming StockMove(s) and one combined JE for storable, asset, and expense lines.
Dispatches VendorBillConfirmed which triggers UpdateProductInventoryStatsAction (not valuation jobs).
Missing central receipt “picking” entity; no backorders or partial receipts.
Sales order fulfillment and stock allocation
Operates from Invoice; creates Done stock moves immediately and triggers COGS (OK for Anglo-Saxon).
No reservations/allocations or multi-step (pick/pack/ship) processes.
Destination location is mis-set to Vendor instead of Customer (bug).
Inventory adjustments and corrections
AdjustInventoryAction → InventoryValuationService::adjustInventoryValue posts a simplified JE (Inventory Adjustment Dr / Inventory Cr) using average cost; does not create StockMove of type Adjustment or track quantity by location. No physical traceability.
Multi-location/warehouse inventory tracking
StockLocation has types: Internal, Customer, Vendor, InventoryAdjustment, and parent/children hierarchy.
Company has defaultStockLocation and vendorLocation; no customer default.
No StockQuant table; Product.quantity_on_hand is a single counter (not per location). This blocks location-level availability and reservations.
Cost calculation methods (FIFO, LIFO, AVCO, Standard)
Outgoing side implemented correctly for AVCO and FIFO/LIFO.
Incoming layers for FIFO/LIFO only created if the incoming valuation flow runs; currently it doesn’t for purchases, so FIFO/LIFO can’t work correctly on incoming.
Standard costing not implemented. 2) Odoo Best Practices (from Odoo expert)
Stock movement workflows and automation
Push/pull rules and routes automate moves; multi-step pick-pack-ship; automatic backorders; reservations at SO confirmation.
Inventory valuation and costing
FIFO, AVCO, Standard; real-time accounting integration; Anglo-Saxon vs Continental; valuation layers per move to reconcile accounting.
Multi-warehouse/location
Virtual locations (scrap, transit, cross-dock), hierarchical locations, dropshipping/transit flows.
Stock forecasting and reordering
Min/max reordering rules, MTO, safety stock, lead times; automatic procurement (PO/RFQ) generation.
Lot/serial tracking
End-to-end traceability, expiration, FEFO removal strategy, compliance/audit.
Inventory reporting and analytics
Ageing, turnover, stock valuation layers reporting, availability and replenishment reports.
Integration with purchasing and sales
Receipts via pickings and moves; dropshipping; backorders; reservations; packages; accounting entries aligned with valuation layers. 3) Comparative Analysis
Strengths
Solid domain decomposition: StockMove, StockLocation, InventoryCostLayer, StockMoveValuation.
Clear action/service layering and Money casting; strong accounting integration and lock date enforcement.
Outgoing COGS flow is close to best practice: valuation creates JE and valuation record.
Audit logging for StockMove is in place.
Weaknesses and gaps
Inconsistent incoming valuation path:
Purchases bypass InventoryValuationService and valuation layers; AVCO/FIFO/LIFO not consistently applied on incoming; no StockMoveValuation created; risk of accounting duplication vs. bill JE.
Incorrect destination for sales moves (to vendor rather than customer).
No reservations, backorders, or pick-pack-ship; stock moves go straight to Done.
No StockQuant model; cannot represent per-location on-hand or drive availability.
No reorder rules or alerts; no forecasting.
No lot/serial tracking, FEFO, expiration handling.
Standard cost not implemented despite enum option.
Adjustments don’t produce stock moves; no location traceability for adjustments.
Multi-warehouse support is minimal; no routes/push-pull rules; no transit or scrap locations in flows.
UI coverage (Filament resources) for inventory entities is missing.
Missing Odoo features
Routes and rules; pickings; reservations and backorders.
StockQuant and availability; reordering rules and scheduler.
Lot/serial/batch management; FEFO.
Comprehensive valuation layer reporting; stock ageing/turnover analytics.
Dropshipping and cross-docking routes.
Specific recommendations
Unify all valuation/accounting through InventoryValuationService for BOTH incoming and outgoing to create StockMoveValuation consistently.
Align purchase accounting to Anglo-Saxon:
Receiving (stock move done) posts Inventory Dr / Stock Input Cr via valuation service.
Vendor bill posts Stock Input Dr / AP Cr (not Inventory again), linking to the receipt/valuation layers.
Implement Standard cost branch, or temporarily restrict to AVCO/FIFO/LIFO.
Introduce StockQuant per product+location; migrate away from Product.quantity_on_hand as source of truth.
Fix sales move destination to Customer and add a default customer location on Company or derive per partner.
Add StockPicking and multi-step flows; enable reservations and backorders.
Add ReorderingRule with min/max/safety stock; periodic scheduler job.
Add Lot and (optionally) StockMoveLine with lot/quantity granularity; FEFO for expirables.
Make inventory adjustments produce StockMove(s) between InventoryAdjustment and Internal locations, plus JE according to valuation method.
Filament resources for inventory: Products, Locations, Warehouses, Pickings, Moves, Quants, Cost Layers, Valuations, Reorder Rules, Lots.
Priority ranking (business impact first)
P0 Critical correctness
Unify and fix incoming valuation vs. bill accounting to prevent double/incorrect postings.
Fix sales move destination to Customer.
Implement Standard cost handling or disable it.
P1 Core capability
StockQuant model and availability checks.
Create valuation layers on incoming (FIFO/LIFO) via jobs for VendorBills.
Reservations and backorders; introduce Pickings.
P2 Advanced operations
Reordering rules + scheduler.
Lot/serial tracking with FEFO/expiration.
Proper inventory adjustments with moves and valuation.
P3 Analytics and UX
Valuation/turnover/ageing reports.
Filament UI coverage for inventory. 4) Implementation Roadmap (Laravel/Filament)
Phase 1 — Correctness and consistency (P0)
Incoming valuation flow
Adjust VendorBillService::post to dispatch StockMoveConfirmed for each created Incoming StockMove. Let ProcessIncomingStockAction drive:
AVCO: update average cost + quantity.
FIFO/LIFO: create InventoryCostLayer rows.
Create StockMoveValuation entry with JE Inventory Dr / Stock Input Cr.
Update CreateJournalEntryForVendorBillAction (or its usage) to NOT debit Inventory on posting the bill; instead Dr Stock Input, Cr Accounts Payable, tying to the receipt side. This aligns with Anglo-Saxon accounting.
Sales destination fix
In CreateStockMovesForInvoiceAction, change to_location_id to a Customer location (add company->customerLocation or derive from partner).
Standard cost handling
Either implement STANDARD branch in InventoryValuationService (COGS from standard price, variances posted to price difference account) or forbid STANDARD in UI/enum for now.
Phase 2 — Core inventory structures (P1)
StockQuant
New table: product_id, location_id, qty, company_id, lot_id nullable.
Update on every StockMove Done; use DB-level locking to avoid race conditions.
Expose aggregate product on-hand as a computed field or maintain a denormalized product.quantity_on_hand from quants.
Pickings and reservations
Add StockPicking (type: receipt, delivery, internal; states: draft/confirmed/assigned/done; relates to partner, scheduled_date).
Create StockMove lines under a picking; reservation confirms availability (via quants) and decrements “available to reserve” counters.
Backorders: split remaining quantities into a new picking when partial done.
Incoming layers for FIFO/LIFO
Ensure ProcessIncomingStockAction creates cost layers for each receipt; link cost layers to source and picking.
Phase 3 — Planning and traceability (P2)
Reordering rules
ReorderingRule: product_id, location_id, min_qty, max_qty, multiple, lead_time, safety_stock, route (MTO/min-max).
Scheduler command to compute demand and create purchase requests/bills or draft pickings depending on route.
Lot/serial tracking
Lot: product_id, lot_code, expiration_date.
StockMoveLine: move_id, lot_id, quantity (facilitates partial/lotted moves).
FEFO removal strategy for products with expiration; apply at reservation.
Inventory adjustments
Adjustment document creates StockMove(s) between Internal and InventoryAdjustment locations; valuation service posts JE accordingly.
Add a count-based adjustment mode to reconcile Quants to a counted value.
Phase 4 — Reporting and Filament UI (P3)
Reporting
Valuation reports leveraging StockMoveValuation and InventoryCostLayer.
Turnover and ageing using quants and move histories.
Filament resources/pages
StockLocations, Warehouses (if introduced), StockPickings (list/detail/steps), StockMoves, StockQuants, InventoryCostLayers, StockMoveValuations, ReorderingRules, Lots.
Tests and TDD
Follow your preference: write comprehensive tests first that mimic Odoo-like flows (receipts, deliveries, backorders, AVCO/FIFO/LIFO valuation integrity, lot FEFO), then implement until green.
Run Laravel tests with --parallel as per your policy.
Concrete Technical Changes (high level)
Fix sales destination
Change to Customer location, supply default or derive per invoice partner.
Dispatch StockMoveConfirmed consistently.
VendorBill flow alignment
Dispatch StockMoveConfirmed on incoming moves.
Let ProcessIncomingStockAction create valuation JE and valuation record; ensure CreateJournalEntryForVendorBillAction posts to Stock Input rather than Inventory.
Add missing branches
Implement STANDARD valuation handling or disable it in enum/UI.
Introduce StockQuant and Pickings
New migrations, models, services, and listeners to update Quants on move completion and handle reservations/backorders.
Add ReorderingRule and scheduler
Console command + service to compute and create replenishments.
Lot/serial and FEFO
Lot + MoveLine models; integrate with reservation strategy.
Inventory adjustments
Actions and services to create moves + JE for adjustments, tracked by location.
Notes on consistency with your preferences
Business logic in Services/Actions, Observers only for side effects (audit/logging) — maintained.
Money casting via Brick\Money — continue; Quants are counts, valuations use Money types.
TDD approach — write failing tests for the incoming valuation path and sales destination bug; then fix.
Filament v4 resources — implement compact, workflow-focused screens for Pickings, Quants, Reordering.
Optional quick win snippets
Sales: fix “to” location from vendor to customer

CreateStockMovesForInvoiceAction.php
app/Actions/Sales
VendorBill: dispatch StockMoveConfirmed after creating each incoming move

VendorBillService.php
app/Services
Next steps
Confirm whether you prefer strict Anglo-Saxon flows (recommended) so we refactor VendorBill posting and incoming valuation accordingly.
Approve the Phase 1 changes; I’ll then prepare migrations, actions/services updates, and the first test suite for:
Sales destination fix,
Incoming valuation via service for VendorBill,
Standard cost behavior decision.
