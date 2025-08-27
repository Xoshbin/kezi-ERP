<?php

namespace App\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\DataTransferObjects\Purchases\VendorBillLineDTO;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Widgets\SettlementSummaryWidget;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Journal;
use App\Models\VendorBill;
use App\Models\VendorBillAttachment;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    protected array $newAttachments = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label(__('vendor_bill.confirm'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Draft)
                ->action(function (VendorBill $record): void {
                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $vendorBillService->confirm($record, Auth::user());
                        Notification::make()->title(__('vendor_bill.notification_bill_confirmed_success'))->success()->send();
                    } catch (Exception $e) {
                        Notification::make()->title(__('vendor_bill.notification_confirm_bill_error'))->body($e->getMessage())->danger()->send();
                    }
                }),

            // Actions\Action::make('resetToDraft')
            //     ->label(__('vendor_bill.reset_to_draft'))
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Posted)
            //     ->form([
            //         Forms\Components\Textarea::make('reason')->label(__('vendor_bill.reason'))->required(),
            //     ])
            //     ->action(function (VendorBill $record, array $data): void {
            //         $vendorBillService = app(VendorBillService::class);
            //         try {
            //             $vendorBillService->resetToDraft($record, Auth::user(), $data['reason']);
            //             Notification::make()->title(__('vendor_bill.notification_bill_reset_success'))->success()->send();
            //         } catch (\Exception $e) {
            //             Notification::make()->title(__('vendor_bill.notification_reset_bill_error'))->body($e->getMessage())->danger()->send();
            //         }
            //     }),

            Action::make('register_payment')
                ->label(__('Register Payment'))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->modalHeading(__('Register Payment'))
                ->modalDescription(__('Register a payment for this vendor bill'))
                ->form([
                    Select::make('journal_id')
                        ->label('Journal')
                        ->options(function () {
                            return Journal::where('company_id', \Filament\Facades\Filament::getTenant()->id)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->default(function () {
                            return Journal::where('company_id', \Filament\Facades\Filament::getTenant()->id)
                                ->where('type', 'bank')
                                ->first()?->id;
                        }),
                    DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),
                    MoneyInput::make('amount')
                        ->label('Amount')
                        ->currencyField('currency_id')
                        ->default(fn(VendorBill $record) => $record->getRemainingAmount())
                        ->required(),
                    TextInput::make('reference')
                        ->label('Reference')
                        ->placeholder('Optional reference'),
                    Hidden::make('currency_id')
                        ->default(fn(VendorBill $record) => $record->currency_id),
                ])
                ->action(function (VendorBill $record, array $data) {
                    try {
                        $currency = $record->currency;

                        // Create payment document link DTO
                        $documentLink = new CreatePaymentDocumentLinkDTO(
                            document_type: 'vendor_bill',
                            document_id: $record->id,
                            amount_applied: Money::of($data['amount'], $currency->code)
                        );

                        // Create payment DTO
                        $paymentDTO = new CreatePaymentDTO(
                            company_id: \Filament\Facades\Filament::getTenant()->id,
                            journal_id: $data['journal_id'],
                            currency_id: $record->currency_id,
                            payment_date: $data['payment_date'],
                            payment_purpose: PaymentPurpose::Settlement,
                            payment_type: PaymentType::Outbound,
                            partner_id: $record->vendor_id,
                            amount: Money::of($data['amount'], $currency->code),
                            counterpart_account_id: null,
                            document_links: [$documentLink],
                            reference: $data['reference']
                        );

                        // Create and confirm payment
                        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, Auth::user());
                        app(\App\Services\PaymentService::class)->confirm($payment, Auth::user());

                        Notification::make()
                            ->title(__('Payment registered successfully'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Error registering payment'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn(VendorBill $record) =>
                    $record->status === VendorBillStatus::Posted &&
                    !$record->getRemainingAmount()->isZero()
                ),

            DeleteAction::make()
                ->action(function (Model $record) {
                    app(VendorBillService::class)->delete($record);
                    $this->redirect(VendorBillResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Store new attachments separately and remove from data for DTO
        $this->newAttachments = $data['attachments'] ?? [];
        unset($data['attachments']);

        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new VendorBillLineDTO(
                product_id: $line['product_id'] ?? null,
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $record->currency->code),
                expense_account_id: $line['expense_account_id'],
                tax_id: $line['tax_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null
            );
        }

        $vendorBillDTO = new UpdateVendorBillDTO(
            vendorBill: $record,
            company_id: \Filament\Facades\Filament::getTenant()->id,
            vendor_id: $data['vendor_id'],
            currency_id: $data['currency_id'],
            bill_reference: $data['bill_reference'],
            bill_date: $data['bill_date'],
            accounting_date: $data['accounting_date'],
            due_date: $data['due_date'] ?? null,
            lines: $lineDTOs,
            updated_by_user_id: Auth::id()
        );

        return app(UpdateVendorBillAction::class)->execute($vendorBillDTO);
    }

    protected function afterSave(): void
    {
        $this->handleFileUploads();
    }

    protected function handleFileUploads(): void
    {
        if (empty($this->newAttachments)) {
            return;
        }

        // Get existing attachment file paths to avoid duplicates
        $existingPaths = $this->record->attachments()->pluck('file_path')->toArray();

        foreach ($this->newAttachments as $filePath) {
            // Skip if this file is already attached
            if (in_array($filePath, $existingPaths)) {
                continue;
            }

            if (Storage::disk('local')->exists($filePath)) {
                $fileInfo = pathinfo($filePath);
                $mimeType = Storage::disk('local')->mimeType($filePath);
                $fileSize = Storage::disk('local')->size($filePath);

                VendorBillAttachment::create([
                    'company_id' => $this->record->company_id,
                    'vendor_bill_id' => $this->record->id,
                    'file_name' => $fileInfo['basename'],
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'uploaded_by_user_id' => Auth::id(),
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('lines', 'currency', 'attachments');

        $linesData = $this->record->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_id' => $line->tax_id,
                'expense_account_id' => $line->expense_account_id,
                'analytic_account_id' => $line->analytic_account_id,
            ];
        })->toArray();

        $attachmentsData = $this->record->attachments->pluck('file_path')->toArray();

        $data['lines'] = $linesData;
        $data['attachments'] = $attachmentsData;

        return $data;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // VendorBillResource\Widgets\AgingAnalysisWidget::class,
            SettlementSummaryWidget::class,
        ];
    }
}
