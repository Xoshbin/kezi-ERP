<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Models\Currency;
use Kezi\Purchase\Actions\Purchases\CreateVendorBillAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillDTO;
use Kezi\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillAttachment;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    /** @var array<string, mixed> */
    protected array $attachments = [];

    public function mount(): void
    {
        parent::mount();

        // Check if we have a purchase_order_id parameter and pre-fill the form
        $purchaseOrderId = request()->query('purchase_order_id');
        if ($purchaseOrderId) {
            $this->fillFormFromPurchaseOrder((int) $purchaseOrderId);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currency = Currency::findOrFail($data['currency_id']);
        // Ensure we have a single Currency model, not a collection
        if ($currency instanceof Collection) {
            $currency = $currency->first();
            if (! $currency) {
                throw new InvalidArgumentException('Currency not found');
            }
        }
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new CreateVendorBillLineDTO(
                product_id: $line['product_id'],
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currency->code, null, RoundingMode::HALF_UP),
                expense_account_id: $line['expense_account_id'],
                tax_id: $line['tax_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null,
                shipping_cost_type: isset($line['shipping_cost_type']) ? \Kezi\Foundation\Enums\ShippingCostType::tryFrom($line['shipping_cost_type']) : null,
                asset_category_id: $line['asset_category_id'] ?? null,
                deferred_start_date: $line['deferred_start_date'] ?? null,
                deferred_end_date: $line['deferred_end_date'] ?? null,
                currency: $currency->code
            );
        }
        $data['lines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Store attachments separately and remove from data for DTO
        $attachments = $data['attachments'] ?? [];
        unset($data['attachments']);

        // Add company_id from tenant context
        $data['company_id'] = (int) (Filament::getTenant()?->getKey() ?? 0);

        // Store exchange_rate_at_creation separately since it's not in the DTO
        $exchangeRate = $data['exchange_rate_at_creation'] ?? null;
        unset($data['exchange_rate_at_creation']);

        if (isset($data['incoterm'])) {
            $data['incoterm'] = Incoterm::tryFrom($data['incoterm']);
        }

        $vendorBillDTO = new CreateVendorBillDTO(...$data);
        $vendorBill = app(CreateVendorBillAction::class)->execute($vendorBillDTO);

        // Set exchange_rate_at_creation if provided
        if ($exchangeRate) {
            $vendorBill->update([
                'exchange_rate_at_creation' => $exchangeRate,
            ]);
        }

        // Store attachments for later processing
        $this->attachments = $attachments;

        return $vendorBill;
    }

    protected function afterCreate(): void
    {
        $this->handleFileUploads();
    }

    protected function handleFileUploads(): void
    {
        if (empty($this->attachments)) {
            return;
        }

        foreach ($this->attachments as $filePath) {
            if (Storage::disk('local')->exists($filePath)) {
                $fileInfo = pathinfo($filePath);
                $mimeType = File::mimeType(Storage::disk('local')->path($filePath));
                $fileSize = Storage::disk('local')->size($filePath);

                VendorBillAttachment::create([
                    'company_id' => $this->getRecord() instanceof VendorBill ? $this->getRecord()->company_id : null,
                    'vendor_bill_id' => $this->getRecord()?->getKey(),
                    'file_name' => $fileInfo['basename'],
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'uploaded_by_user_id' => Auth::id(),
                ]);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('vendor-bills'),
            $this->getLoadFromPurchaseOrderAction(),
        ];
    }

    protected function fillFormFromPurchaseOrder(int $purchaseOrderId): void
    {
        $purchaseOrder = PurchaseOrder::with(['lines.product', 'vendor', 'currency'])
            ->find($purchaseOrderId);

        if (! $purchaseOrder) {
            return;
        }

        // Validate that the PO can be billed
        if (! $purchaseOrder->status->canCreateBill()) {
            \Filament\Notifications\Notification::make()
                ->title(__('accounting::bill.errors.cannot_create_bill_title'))
                ->body(__('accounting::bill.errors.cannot_create_bill_body', ['status' => $purchaseOrder->status->label()]))
                ->warning()
                ->send();

            return;
        }

        // Pre-fill the form with PO data
        $formData = [
            'vendor_id' => $purchaseOrder->vendor_id,
            'currency_id' => $purchaseOrder->currency_id,
            'bill_reference' => '', // User should fill this
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'due_date' => null,
            'payment_term_id' => null,
            'exchange_rate_at_creation' => $purchaseOrder->exchange_rate_at_creation,
            'purchase_order_id' => $purchaseOrder->id,
            'lines' => [],
        ];

        // Transform PO lines to vendor bill lines
        foreach ($purchaseOrder->lines as $poLine) {
            if ($poLine->product && $poLine->product->expense_account_id) {
                $formData['lines'][] = [
                    'product_id' => $poLine->product_id,
                    'description' => $poLine->description,
                    'quantity' => $poLine->quantity,
                    'unit_price' => $poLine->unit_price->getAmount()->toFloat(),
                    'expense_account_id' => $poLine->product->expense_account_id,
                    'tax_id' => $poLine->tax_id,
                    'analytic_account_id' => null,
                ];
            }
        }

        $this->form->fill($formData);
    }

    protected function getLoadFromPurchaseOrderAction(): Action
    {
        return Action::make('loadFromPurchaseOrder')
            ->label(__('accounting::bill.actions.load_from_purchase_order'))
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->fillForm([
                'purchase_order_id' => null,
            ])
            ->schema([
                Select::make('purchase_order_id')
                    ->label(__('accounting::bill.purchase_order'))
                    ->options(function (): array {
                        // Use getRawState() to avoid triggering validation
                        $vendorId = $this->form->getRawState()['vendor_id'] ?? null;

                        if (! $vendorId) {
                            return [];
                        }

                        return PurchaseOrder::where('vendor_id', $vendorId)
                            ->whereIn('status', ['confirmed', 'to_receive', 'partially_received', 'fully_received', 'to_bill', 'partially_billed'])
                            ->get()
                            ->mapWithKeys(fn ($po) => [$po->id => "{$po->po_number} - {$po->reference}"])
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->fillFormFromPurchaseOrder($data['purchase_order_id']);
            })
            ->visible(function (): bool {
                // Only show if vendor is selected and no PO is already loaded
                // Use getRawState() to avoid triggering validation
                try {
                    $formState = $this->form->getRawState();

                    return ! empty($formState['vendor_id']) && empty($formState['purchase_order_id']);
                } catch (Exception $e) {
                    // If form state can't be retrieved, hide the action
                    return false;
                }
            });
    }
}
