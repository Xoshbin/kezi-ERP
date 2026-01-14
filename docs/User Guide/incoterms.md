---
title: Understanding Incoterms
slug: incoterms
---

# Understanding Incoterms (Delivery Terms)

Incoterms® (International Commercial Terms) are standardized trade terms published by the International Chamber of Commerce (ICC). They clearly define the responsibilities of buyers and sellers for the delivery of goods under sales contracts.

This application creates a record of the agreed Incoterm on all commercial documents (Sales Orders, Invoices, Purchase Orders, Vendor Bills) to ensure clarity regarding shipping costs, insurance, and risk transfer.

## Supported Incoterms

The system supports the 11 standard Incoterms® 2020 rules:

### Rules for Any Mode of Transport
*   **EXW (Ex Works):** Buyer responsbile for everything. Seller just makes goods available at their premises.
*   **FCA (Free Carrier):** Seller delivers to a carrier or another person nominated by the buyer at the seller's premises or another named place.
*   **CPT (Carriage Paid To):** Seller delivers to a carrier and pays for carriage to the named destination. Risk transfers upon delivery to first carrier.
*   **CIP (Carriage and Insurance Paid To):** Same as CPT, but seller also pays for insurance.
*   **DAP (Delivered at Place):** Seller delivers when goods are placed at the disposal of the buyer on the arriving means of transport ready for unloading at the named place of destination.
*   **DPU (Delivered at Place Unloaded):** Seller delivers when goods, once unloaded, are placed at the disposal of the buyer at a named place of destination.
*   **DDP (Delivered Duty Paid):** Seller responsbile for everything, including import duties and taxes, delivering to the buyer's premises.

### Rules for Sea and Inland Waterway Transport
*   **FAS (Free Alongside Ship):** Seller delivers when goods are placed alongside the vessel (e.g., on a quay or a barge) nominated by the buyer at the named port of shipment.
*   **FOB (Free On Board):** Seller delivers when goods are on board the vessel nominated by the buyer at the named port of shipment.
*   **CFR (Cost and Freight):** Seller delivers on board. Seller pays for costs and freight to the named port of destination.
*   **CIF (Cost, Insurance and Freight):** Same as CFR, but seller also pays for minimum insurance cover.

## Automated Validation & Guidance

The system now provides automated validation for shipping-related costs based on the selected Incoterm. 

### Shipping Cost Types
When adding expense lines to a **Vendor Bill** or **Purchase Order**, you can categorize shipping costs using the **Shipping Type** field:
*   **Freight:** Main carriage/transportation costs.
*   **Insurance:** Cargo/transit insurance.
*   **Customs Duty:** Import duties and tariffs.
*   **Handling:** Loading/unloading, document handling.
*   **Port Charges:** Terminal fees, port handling.

### How Validation Works
When you select an Incoterm, the system identifies which costs are typically the buyer's responsibility:
*   **Warning Banners:** If you add a cost that the seller is typically responsible for (e.g., adding a "Freight" line to a **DDP** bill), a warning banner will appear at the top of the form.
*   **Non-Blocking:** These warnings are for guidance only and will not prevent you from posting the bill, as businesses may have special agreements that differ from standard Incoterms.

## Logic Overview
The system implements the following responsibility rules:
*   **Seller Pays Freight:** CPT, CIP, DAP, DPU, DDP, CFR, CIF.
*   **Seller Pays Insurance:** CIP, CIF, DDP.
*   **Seller Handles Export Clearance:** All except EXW.
*   **Seller Handles Import Clearance:** DDP only.

This logic ensures that your shipping costs are aligned with your international trade agreements.
