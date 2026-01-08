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

## How to Use Incoterms

### Sales & Invoicing
When creating a **Sales Order** or **Customer Invoice**, you will find the **Incoterm** field in the "Additional Information" or "Shipping" section (depending on layout).

1.  Select the agreed Incoterm from the dropdown list.
2.  The system will store this information.
3.  Future updates (coming soon) may use this to automatically suggest shipping cost lines.

### Purchases & Bills
When creating a **Purchase Order** or **Vendor Bill**:

1.  Select the Incoterm specified by your vendor.
2.  This helps your accounting team verify if freight charges on the bill are legitimate. For example, if the term is **DDP**, you should generally not see separate freight charges from the vendor.

## Logic Overview
The system understands the basic responsibility shift for each term:
*   **Seller Pays Freight:** CPT, CIP, DAP, DPU, DDP, CFR, CIF.
*   **Buyer Pays Freight:** EXW, FCA, FAS, FOB.

This logic is currently used for reporting and will be used for automated validation in future updates.
