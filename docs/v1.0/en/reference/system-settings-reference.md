---
title: System Settings Reference
icon: heroicon-o-cog
---

# System Settings Reference

This reference details the key configuration options available in the System Settings.

## General Settings

| Field | Description | Example |
| :--- | :--- | :--- |
| **Company Name** | The display name of the tenant. | Kezi Int. |
| **Tax ID** | The tax registration number used on legal documents. | 123-456-789 |
| **Primary Color** | The main branding color for the UI and PDF headers. | #3b82f6 |
| **Logo** | The image file used for branding. | `logo.png` |

## Localization

| Field | Description | Example |
| :--- | :--- | :--- |
| **Default Currency** | The base currency for financial reporting. | USD ($) |
| **Date Format** | How dates are displayed system-wide. | YYYY-MM-DD |
| **Timezone** | The local timezone for the company. | UTC+3 |

## Numbering Settings

Controls how document numbers are generated.

| Setting | Format Example | Description |
| :--- | :--- | :--- |
| **Invoice Prefix** | `INV-2024-` | Prefix for sales invoices. |
| **Bill Prefix** | `BILL-2024-` | Prefix for vendor bills. |
| **Padding** | `00001` | Number of digits in the sequence (e.g., 5 padding). |

## PDF Settings

Controls the layout and content of generated PDFs.

| Option | Description |
| :--- | :--- |
| **Paper Size** | A4 or Letter. |
| **Header Layout** | Logo Left, Logo Right, or Centered. |
| **Footer Text** | Custom text to appear at the bottom (e.g., bank details). |
| **Show Tax ID** | Toggle to display the Tax ID on documents. |

## See Also

- [Setting Up Your Company](../tutorials/setting-up-your-company.md)
- [Managing Users](../how-to/manage-users.md)
