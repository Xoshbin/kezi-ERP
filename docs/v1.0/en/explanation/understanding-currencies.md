# Understanding Currencies

Kezi supports multi-currency operations, allowing you to transact in any currency while maintaining books in your base currency.

## Overview

Currency management includes defining currencies, setting exchange rates, and handling automatic conversion. All financial reports can be viewed in either the transaction currency or your base currency.

## Key Concepts

### Base Currency

Your company's primary currency, used for:
*   All financial reports
*   Tax calculations
*   Regulatory filings

### Foreign Currency

Any currency other than your base currency. Foreign currency transactions are recorded at the exchange rate on the transaction date.

### Exchange Rates

The conversion rate between currencies. Kezi maintains:
*   **Spot Rates**: Daily rates for transaction conversion
*   **Historical Rates**: Rates used for past transactions (immutable)

## Setting Up Currencies

### Creating a Currency

1.  Navigate to **Settings > Currencies**.
2.  Click **Create Currency**.
3.  Enter:
    *   **Code**: ISO 4217 code (e.g., USD, EUR, IQD)
    *   **Name**: Full name (e.g., "US Dollar")
    *   **Symbol**: Currency symbol (e.g., $, €)
    *   **Decimal Places**: Usually 2 (some currencies use 0 or 3)

### Managing Exchange Rates

1.  Navigate to **Settings > Currency Rates**.
2.  Create rates for each currency pair.
3.  Enter:
    *   **Base Currency**: Your company's currency
    *   **Foreign Currency**: The other currency
    *   **Rate**: How many units of foreign currency equal one base unit
    *   **Date**: When this rate applies

## Multi-Currency Transactions

When creating invoices, bills, or payments in foreign currency:

1.  Select the foreign currency
2.  Current exchange rate is applied automatically
3.  System records both original and converted amounts

## Currency Gain/Loss

When exchange rates change between transaction and payment dates:
*   **Realized Gain/Loss**: Recorded when payment is received/made
*   **Unrealized Gain/Loss**: Calculated during revaluation for open items

See [Currency Revaluation](currency-revaluation.md) for details.

## Best Practices

1.  **Update Rates Regularly**: Keep exchange rates current
2.  **Document Rate Sources**: Note where rates come from
3.  **Revalue Monthly**: Run revaluation at period end
4.  **Review Variances**: Monitor currency gain/loss accounts

## Related Topics

*   [Currency Revaluation](currency-revaluation.md)
*   [Opening Balances](opening-balances.md)
