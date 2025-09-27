#!/bin/bash
# This script moves test files from the root tests/ directory into their respective modules.
# Run this script from the root of your Laravel project.

set -e # Exit immediately if a command exits with a non-zero status.

echo "🚀 Starting Test file organization..."

SOURCE_DIR="tests"
BASE_TARGET_DIR="Modules"

# Helper function to move a file or directory and create its destination path
move_item() {
    local source_item=$1
    local target_module=$2
    local target_path="${BASE_TARGET_DIR}/${target_module}/${source_item}"

    mkdir -p "$(dirname "$target_path")"
    if [ -e "$source_item" ]; then
        echo "Moving ${source_item} to ${target_module} module..."
        mv "$source_item" "$target_path"
    else
        echo "Warning: Source ${source_item} not found. Skipping."
    fi
}

# --- Foundation Module ---
move_item "tests/Unit/Enums/NumberingTypeTest.php" "Foundation"
move_item "tests/Unit/Livewire/Synthesizers/MoneySynthTest.php" "Foundation"
move_item "tests/Feature/Casts" "Foundation"
move_item "tests/Feature/General/DeletionNotAllowedTest.php" "Foundation"
move_item "tests/Feature/General/RelationshipDeletionNotAllowedTest.php" "Foundation"
move_item "tests/Feature/PaymentTerms" "Foundation"
move_item "tests/Feature/Rules/NumberingSettingsChangeRuleTest.php" "Foundation"
move_item "tests/Feature/Seeders" "Foundation"
move_item "tests/Feature/CurrencyConverterServiceTest.php" "Foundation"
move_item "tests/Feature/CurrencyRateTest.php" "Foundation"
move_item "tests/Feature/LocaleControllerTest.php" "Foundation"
move_item "tests/Feature/NumberFormatterTest.php" "Foundation"
move_item "tests/Feature/RefactoredMultiCurrencyServicesTest.php" "Foundation"
move_item "tests/Feature/SequenceServiceTest.php" "Foundation"
move_item "tests/Feature/TranslatableSearchTest.php" "Foundation"
move_item "tests/Feature/TranslatableSelectIntegrationTest.php" "Foundation"
move_item "tests/Feature/TranslatableSelectModalTest.php" "Foundation"
move_item "tests/Feature/TranslatableSelectSearchTest.php" "Foundation"
move_item "tests/Feature/Filament/CurrencyRateResourceTest.php" "Foundation"
move_item "tests/Feature/Filament/PdfSettingsResourceTest.php" "Foundation"
move_item "tests/Feature/Filament/MoneySynthLivewireIntegrationTest.php" "Foundation"
move_item "tests/Feature/Services/SequenceServiceTest.php" "Foundation"
move_item "tests/Feature/Services/ExchangeRateHistoricalFallbackTest.php" "Foundation"

# --- Product Module ---
move_item "tests/Feature/ProductFactoryTest.php" "Product"
move_item "tests/Feature/ProductResourceCreateOptionFormTest.php" "Product"
move_item "tests/Feature/ProductCreationIntegrityTest.php" "Product"
move_item "tests/Feature/Filament/Resources/ProductResourceTest.php" "Product"

# --- Accounting Module ---
move_item "tests/Unit/Services" "Accounting"
move_item "tests/Feature/Accounting" "Accounting"
move_item "tests/Feature/Actions/Accounting" "Accounting"
move_item "tests/Feature/Actions/Loans" "Accounting"
move_item "tests/Feature/Actions/Reconciliation" "Accounting"
move_item "tests/Feature/Assets" "Accounting"
move_item "tests/Feature/Bank" "Accounting"
move_item "tests/Feature/CoreAccounting" "Accounting"
move_item "tests/Feature/Integration/BankReconciliationIntegrationTest.php" "Accounting"
move_item "tests/Feature/Livewire" "Accounting"
move_item "tests/Feature/Models/PartnerFinancialTest.php" "Accounting"
move_item "tests/Feature/InvoicePostingIntegrationTest.php" "Accounting"
move_item "tests/Feature/JournalEntryMultiCurrencyTest.php" "Accounting"
move_item "tests/Feature/MultiCurrencyVendorBillInvoiceTest.php" "Accounting"
move_item "tests/Feature/WebInterfaceInvoicePostingTest.php" "Accounting"
move_item "tests/Feature/Filament/BankReconciliationAccessControlTest.php" "Accounting"
move_item "tests/Feature/Filament/BankStatementResourceTest.php" "Accounting"
move_item "tests/Feature/Filament/ConsolidatedExchangeRateFieldTest.php" "Accounting"
move_item "tests/Feature/Filament/ExchangeRateFieldTest.php" "Accounting"
move_item "tests/Feature/Filament/ExchangeRateJournalEntryTest.php" "Accounting"
move_item "tests/Feature/Filament/LoanAgreementDocsLinksTest.php" "Accounting"
move_item "tests/Feature/Filament/LoanAgreementFormInlineCreateTest.php" "Accounting"
move_item "tests/Feature/Filament/LoanAgreementHeaderActionsTest.php" "Accounting"
move_item "tests/Feature/Filament/LoanAgreementInlineCreateTest.php" "Accounting"
move_item "tests/Feature/Filament/LoanAgreementResourceTest.php" "Accounting"
move_item "tests/Feature/Filament/Pages/DashboardTest.php" "Accounting"
move_item "tests/Feature/Filament/Pages/Reports" "Accounting"
move_item "tests/Feature/Filament/Reports" "Accounting"
move_item "tests/Feature/Filament/Resources/JournalEntryResourceTest.php" "Accounting"
move_item "tests/Feature/Filament/Widgets" "Accounting"
move_item "tests/Feature/Services/Reports" "Accounting"
move_item "tests/Feature/Services/BankReconciliationServiceReconciliationControlTest.php" "Accounting"
move_item "tests/Feature/Services/BankReconciliationServiceTest.php" "Accounting"

# --- Sales Module ---
move_item "tests/Feature/Actions/Sales" "Sales"
move_item "tests/Feature/Models/InvoiceLineTest.php" "Sales"
move_item "tests/Feature/Sales" "Sales"
move_item "tests/Feature/InvoiceNumberRaceConditionTest.php" "Sales"
move_item "tests/Feature/PdfMultiLanguageTest.php" "Sales"
move_item "tests/Feature/PdfRoutesTest.php" "Sales"
move_item "tests/Feature/Filament/InvoiceEditPageTest.php" "Sales"
move_item "tests/Feature/Filament/InvoiceResourceTest.php" "Sales"
move_item "tests/Feature/Services/InvoiceNumberingIntegrationTest.php" "Sales"

# --- Purchase Module ---
move_item "tests/Browser/PurchaseOrderLineItemsBrowserTest.php" "Purchase"
move_item "tests/Feature/Models/PurchaseOrderStatusUpdateTest.php" "Purchase"
move_item "tests/Feature/Purchases" "Purchase"
move_item "tests/Feature/VendorBillAttachmentTest.php" "Purchase"
move_item "tests/Feature/Filament/PurchaseOrderBusinessRulesTest.php" "Purchase"
move_item "tests/Feature/Filament/PurchaseOrderCreateBillActionTest.php" "Purchase"
move_item "tests/Feature/Filament/PurchaseOrderLineItemsTest.php" "Purchase"
move_item "tests/Feature/Filament/PurchaseOrderResourceTest.php" "Purchase"
move_item "tests/Feature/Filament/PurchaseOrderVendorBillFilamentTest.php" "Purchase"
move_item "tests/Feature/Filament/Resources/VendorBillResourceTest.php" "Purchase"
move_item "tests/Feature/Filament/VendorBillAttachmentFilamentTest.php" "Purchase"
move_item "tests/Feature/Filament/VendorBillResourceTest.php" "Purchase"
move_item "tests/Feature/Services/VendorBillNumberingIntegrationTest.php" "Purchase"

# --- Inventory Module ---
move_item "tests/Feature/Adjustments" "Inventory"
move_item "tests/Feature/Inventory" "Inventory"
move_item "tests/Feature/Filament/AdjustmentDocumentResourceTest.php" "Inventory"
move_item "tests/Feature/Filament/InventoryFilamentUIVerificationTest.php" "Inventory"
move_item "tests/Feature/Filament/ManualStockMoveFilamentTest.php" "Inventory"
move_item "tests/Feature/Filament/MoneyInputAdjustmentDocumentProductSelectionTest.php" "Inventory"
move_item "tests/Feature/Filament/StockManagementTranslationsTest.php" "Inventory"
move_item "tests/Feature/Filament/StockMoveConfirmActionTest.php" "Inventory"
move_item "tests/Feature/Filament/StockPickingOperationsTest.php" "Inventory"
move_item "tests/Feature/Services/StockQuantServiceTest.php" "Inventory"

# --- Payment Module ---
move_item "tests/Unit/Enums/PaymentMethodTest.php" "Payment"
move_item "tests/Feature/Payments" "Payment"
move_item "tests/Feature/FinancialTransactions" "Payment"
move_item "tests/Feature/MultiCurrencyPaymentTest.php" "Payment"
move_item "tests/Feature/Traits/HasPaymentStateTest.php" "Payment"
move_item "tests/Feature/Filament/PaymentDocsLinksTest.php" "Payment"
move_item "tests/Feature/Filament/PaymentMethodIntegrationTest.php" "Payment"
move_item "tests/Feature/Filament/PaymentResourceTest.php" "Payment"
move_item "tests/Feature/Filament/PaymentTermSelectionTest.php" "Payment"

# --- HR Module ---
move_item "tests/Feature/HumanResources" "HR"
move_item "tests/Feature/PayrollTest.php" "HR"

# --- Final Cleanup of moved directories ---
# rm -rf tests/Browser
# rm -rf tests/Feature
# rm -rf tests/Unit

echo "✅ Test file organization complete!"
