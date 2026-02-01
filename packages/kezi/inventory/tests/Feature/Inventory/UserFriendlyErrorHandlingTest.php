<?php

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Services\Inventory\UserFriendlyErrorService;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();

    $this->product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::FIFO,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $this->vendorLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor Location',
    ]);

    $this->stockLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Stock Location',
    ]);
});

it('converts InsufficientCostInformationException to user-friendly error data', function () {
    $exception = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills', 'average_cost', 'cost_layer']
    );

    $errorService = app(UserFriendlyErrorService::class);
    $errorData = $errorService->convertCostInformationException($exception);

    expect($errorData)->toHaveKeys([
        'title',
        'message',
        'explanation',
        'primary_solution',
        'next_steps',
        'help_text',
    ]);

    expect($errorData['title'])->toBe(__('inventory::inventory_accounting.cost_validation_errors.title'));
    expect($errorData['message'])->toContain($this->product->name);
    expect($errorData['explanation'])->toContain('First In, First Out');
    expect($errorData['next_steps'])->toBeArray();
    expect($errorData['next_steps'])->not->toBeEmpty();
});

it('generates appropriate notification message for products without vendor bills', function () {
    $exception = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills']
    );

    $errorService = app(UserFriendlyErrorService::class);
    $message = $errorService->getNotificationMessage($exception);

    expect($message)->toContain($this->product->name);
    expect($message)->toContain('create and confirm a vendor bill');
    expect($message)->not->toContain('cost layers'); // Should avoid technical jargon
    expect($message)->not->toContain('FIFO'); // Should avoid technical jargon
});

it('provides detailed error information for modal dialogs', function () {
    $exception = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills', 'cost_layer']
    );

    $errorService = app(UserFriendlyErrorService::class);
    $details = $errorService->getDetailedErrorInfo($exception);

    expect($details)->toHaveKeys([
        'title',
        'product_name',
        'product_code',
        'valuation_method',
        'explanation',
        'solution',
        'steps',
        'help_text',
    ]);

    expect($details['product_name'])->toBe($this->product->name);
    expect($details['valuation_method'])->toBe('First In, First Out (FIFO)');
    expect($details['steps'])->toBeArray();
    expect($details['steps'])->not->toBeEmpty();
});

it('provides different solutions based on vendor bill status', function () {
    $errorService = app(UserFriendlyErrorService::class);

    // Test product with no vendor bills
    $exception1 = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills']
    );

    $message1 = $errorService->getNotificationMessage($exception1);
    expect($message1)->toContain('create and confirm a vendor bill');

    // Create a draft vendor bill for the product
    $vendorBill = VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'status' => VendorBillStatus::Draft,
    ]);

    VendorBillLine::factory()->create([
        'vendor_bill_id' => $vendorBill->id,
        'company_id' => $this->company->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_price' => Money::of(100, $this->company->currency->code),
    ]);

    $exception2 = new InsufficientCostInformationException(
        product: $this->product->fresh(),
        attemptedSources: ['posted_vendor_bills']
    );

    $message2 = $errorService->getNotificationMessage($exception2);
    expect($message2)->toContain('confirm the existing draft vendor bills');
});

it('uses business terminology instead of technical jargon', function () {
    $exception = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills', 'cost_layer']
    );

    $errorService = app(UserFriendlyErrorService::class);
    $message = $errorService->getNotificationMessage($exception);
    $details = $errorService->getDetailedErrorInfo($exception);

    // Should not contain technical terms
    expect($message)->not->toContain('cost layers');
    expect($message)->not->toContain('valuation method');
    expect($message)->not->toContain('FIFO');
    expect($message)->not->toContain('attempted sources');

    // Should contain business-friendly terms
    expect($message)->toContain('inventory movement');
    expect($message)->toContain('vendor bill');

    // Details can contain more technical information but in user-friendly way
    expect($details['explanation'])->toContain('First In, First Out');
    expect($details['explanation'])->toContain('purchase cost information');
});

it('provides step-by-step instructions for different scenarios', function () {
    $errorService = app(UserFriendlyErrorService::class);

    // Product with no vendor bills
    $exception = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills']
    );

    $details = $errorService->getDetailedErrorInfo($exception);
    $steps = $details['steps'];

    expect($steps)->toHaveCount(4);
    expect($steps[0])->toContain('Go to Vendor Bills');
    expect($steps[1])->toContain('Add this product');
    expect($steps[2])->toContain('Confirm the vendor bill');
    expect($steps[3])->toContain('Return here');
});

it('exception provides user-friendly methods', function () {
    $exception = new InsufficientCostInformationException(
        product: $this->product,
        attemptedSources: ['posted_vendor_bills']
    );

    // Test user-friendly message method
    $message = $exception->getUserFriendlyMessage();
    expect($message)->toBeString();
    expect($message)->toContain($this->product->name);

    // Test user-friendly details method
    $details = $exception->getUserFriendlyDetails();
    expect($details)->toBeArray();
    expect($details)->toHaveKey('product_name');
    expect($details['product_name'])->toBe($this->product->name);

    // Test user-friendly error data method
    $errorData = $exception->getUserFriendlyErrorData();
    expect($errorData)->toBeArray();
    expect($errorData)->toHaveKey('title');
    expect($errorData)->toHaveKey('message');
});

it('handles AVCO valuation method appropriately', function () {
    $avcoProduct = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => ValuationMethod::AVCO,
        'average_cost' => Money::of(0, $this->company->currency->code),
    ]);

    $exception = new InsufficientCostInformationException(
        product: $avcoProduct,
        attemptedSources: ['posted_vendor_bills', 'average_cost']
    );

    $errorService = app(UserFriendlyErrorService::class);
    $details = $errorService->getDetailedErrorInfo($exception);

    expect($details['explanation'])->toContain('Average Cost');
    expect($details['valuation_method'])->toBe('Average Cost (AVCO)');
});
