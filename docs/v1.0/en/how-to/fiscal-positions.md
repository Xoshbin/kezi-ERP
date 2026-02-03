---
title: Fiscal Positions
icon: heroicon-o-globe-alt
order: 4
---

# Fiscal Positions: Smart Tax & Account Mapping

This guide explains how **Fiscal Positions** (also known as Tax Mapping) work in Kezi. This powerful feature ensures your transactions always use the correct taxes and accounts, whether you're selling locally, to another state, or internationally—without manual effort.

---

## What are Fiscal Positions?

Think of a **Fiscal Position** as a set of automatic rules for your transactions. When you create an invoice or sales order, the system looks at your customer's location or status and asks: *"Does this customer need different tax rules?"*

If the answer is yes, the Fiscal Position automatically swaps the standard tax for the correct one.

**Why does this matter?**
1.  **Automation**: You don't need to obscure your product list with duplicate items like "Product A (Local)" and "Product A (Export)". Just have one "Product A".
2.  **Compliance**: Automatically apply 0% VAT for exports or specific rates for different regions.
3.  **Accuracy**: Reduce human error by letting the system choose the right tax and income accounts.

---

## Where to Find It

Navigate to: **Accounting → Configuration → Fiscal Positions**

You'll see a list of all active tax mapping rules.

> **💡 Tip**: Fiscal Positions are applied automatically on Invoices and Sales Orders based on the rules you set, but you can also manually select one if needed.

---

## Creating a Fiscal Position

Let's walk through creating a rule for **Export Sales** (where typically no VAT is charged).

### Step 1: Start Fresh

Navigate to **Accounting → Configuration → Fiscal Positions → Create**

### Step 2: Configure the Basics

| Field | What to Enter | Example |
|-------|---------------|---------|
| **Name** | A clear descriptive name | "Export (0% VAT)" |
| **Company** | The company this rule applies to | Your Company |
| **Auto Apply** | checks to apply this rule automatically | Toggle **ON** |

### Step 3: Set Automatic Detection (Criteria)

If you enabled **Auto Apply**, you can tell the system when to use this rule:

-   **Tax ID Required**: Enable if this rule only applies to business customers with a VAT number.
-   **Country Group**: Select a group (e.g., "Foreign Countries") to apply to all nations in that group.
-   **Country**: Select a specific country if the rule is unique to one location.
-   **Zip Range**: Use "Zip From" and "Zip To" for specific regions (useful for state-specific taxes).

### Step 4: Define Tax Mappings

This is where the magic happens. You tell the system: *"When you see Tax X, replace it with Tax Y."*

1.  Go to the **Tax Mapping** tab.
2.  Click **Add a line**.
3.  **Tax on Product**: Select your standard domestic tax (e.g., "15% Standard VAT").
4.  **Tax to Apply**: Select the replacement tax (e.g., "0% Export VAT").

> **Example**:
> "Replace **15% VAT** with **0% Export VAT**"
> Now, whenever you sell to a customer matching your criteria, the 15% tax on your products will automatically become 0%.

### Step 5: Define Account Mappings (Optional)

Sometimes you need to record income in a different ledger account for specific regions.

1.  Go to the **Account Mapping** tab.
2.  **Account on Product**: Select the standard income account (e.g., "400000 Product Sales").
3.  **Account to Apply**: Select the replacement account (e.g., "400200 Export Sales").

Click **Save** and you're done! 🎉

---

## Common Scenarios

### Scenario 1: Export Sales (Tax Exempt)

**The Situation**: You are based in Iraq (15% VAT) and sell to a customer in Turkey. Exports are tax-exempt (0%).

**Setup:**
1.  Create a Fiscal Position named "**Export**".
2.  Enable **Auto Apply**.
3.  Set **Country** to "Turkey" (or a "Foreign" country group).
4.  **Tax Mapping**: Map "15% Standard VAT" → "0% Export VAT".

**Result**: When you invoice the Turkish customer, Kezi sees their address, finds the "Export" rule, and automatically changes 15% VAT to 0%.

### Scenario 2: B2B vs B2C (VAT Numbers)

**The Situation**: You have different rules for businesses (B2B) vs. individual consumers (B2C) within the same region.

**Setup:**
1.  Create a Fiscal Position named "**B2B Local**".
2.  Enable **Auto Apply**.
3.  Enable **VAT Required**.
4.  **Tax Mapping**: Map your standard consumer tax to the specific B2B tax if different.

**Result**: If the customer contact has a Tax ID filled in, this rule applies. If not, the standard rule applies.

---

## Troubleshooting

### Q: Why isn't my Fiscal Position applying automatically?

**A**: Check these common causes:
1.  **Customer Address**: Does the customer on the invoice have a valid Country/Zip code set?
2.  **Auto Apply**: Is the "Auto Apply" toggle turned on in the Fiscal Position?
3.  **Priority**: If multiple rules match, the system might be picking a different one. Be specific with your criteria.
4.  **Manual Override**: Did someone manually select a different Fiscal Position on the invoice?

### Q: Can I use this for purchasing too?

**A**: Yes! Fiscal Positions work for Vendor Bills as well. You can map standard "Input VAT" to different taxes depending on the vendor's specialized status.

> **💡 Still stuck?** Ensure your Product has a default tax set. The mapping only works if there is a "Tax on Product" to replace!

---

## Related Documentation

-   [Tax Management](tax-management.md) - Setting up the taxes themselves
-   [Invoicing](customer-invoices.md) - Creating customer invoices
-   [Chart of Accounts](chart-of-accounts.md) - Understanding account structures
