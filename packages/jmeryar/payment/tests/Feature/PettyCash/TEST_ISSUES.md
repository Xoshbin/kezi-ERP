# Test Issues - To Be Resolved

## Problem
Tests are failing due to currency factory creating random currency codes that conflict with manually created 'IQD' currency.

## Solution
Tests need to use the `WithConfiguredCompany` trait which likely provides a pre-configured company with currency already set up. This is the pattern used in `MultiCurrencyPaymentTest.php`.

## Action Required
1. Update both test files to use `WithConfiguredCompany` trait
2. Remove manual Currency::factory() calls
3. Use `$this->company` from trait instead of creating new company

## Status
Deferred in favor of completing Filament resources first.
