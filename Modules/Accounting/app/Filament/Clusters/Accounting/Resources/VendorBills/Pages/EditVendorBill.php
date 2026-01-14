<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Accounting\Actions\Accounting\BuildVendorBillPostingPreviewAction;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Widgets\SettlementSummaryWidget;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Services\PaymentService;
use Modules\Purchase\Actions\Purchases\UpdateVendorBillAction;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use Modules\Purchase\DataTransferObjects\Purchases\VendorBillLineDTO;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillAttachment;
use Modules\Purchase\Services\VendorBillService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    /** @var array<string, mixed> */
    protected array $newAttachments = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_posting')
                ->label(__('Preview Posting'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading(__('Posting Preview'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalWidth('7xl')
                ->modalContent(function (VendorBill $record) {
                    $preview = app(BuildVendorBillPostingPreviewAction::class)->execute($record);

                    return view('accounting::filament.clusters.accounting.resources.vendor-bills.pages.preview-posting', [
                        'preview' => $preview,
                        'bill' => $record,
                    ]);
                }),

            Action::make('export_preview_csv')
                ->label(__('Export Preview (CSV)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (VendorBill $record): StreamedResponse {
                    $preview = app(BuildVendorBillPostingPreviewAction::class)->execute($record);
                    $rows = [];
                    $rows[] = ['Account Code', 'Account Name', 'Description', 'Debit', 'Credit'];
                    foreach ($preview['lines'] as $l) {
                        $rows[] = [
                            (string) ($l['account_code'] ?: ''),
                            (string) $l['account_name'],
                            (string) $l['description'],
                            number_format($l['debit_minor'] / 100, 2, '.', ''),
                            number_format($l['credit_minor'] / 100, 2, '.', ''),
                        ];
                    }
                    $csv = '';
                    foreach ($rows as $row) {
                        $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row))."\n";
                    }
                    $filename = 'vendor-bill-'.($record->bill_reference ?: $record->id).'-preview.csv';

                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
            Action::make('export_preview_pdf')
                ->label(__('Export Preview (PDF)'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (VendorBill $record): StreamedResponse {
                    $preview = app(BuildVendorBillPostingPreviewAction::class)->execute($record);
                    $pdf = Pdf::loadView('accounting::filament.clusters.accounting.resources.vendor-bills.pages.preview-posting-pdf', [
                        'preview' => $preview,
                        'bill' => $record,
                    ]);
                    $filename = 'vendor-bill-'.($record->bill_reference ?: $record->id).'-preview.pdf';

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),

            Action::make('post')
                ->label(__('accounting::bill.post'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Draft)
                ->disabled(fn (VendorBill $record): bool => $record->lines->isEmpty() || $record->total_amount->isZero())
                ->action(function (VendorBill $record): void {
                    $this->save();
                    $record = $record->fresh(['lines']);
                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to confirm vendor bill');
                        }
                        $vendorBillService->post($record, $user);
                        Notification::make()->title(__('accounting::bill.notification_bill_confirmed_success'))->success()->send();
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                    } catch (\Modules\Accounting\Exceptions\BudgetExceededException $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    } catch (Exception $e) {
                        Log::error('Vendor bill confirmation failed', [
                            'bill_id' => $record->id,
                            'error' => $e->getMessage(),
                        ]);
                        Notification::make()->title(__('accounting::bill.notification_confirm_bill_error'))->body($e->getMessage())->danger()->persistent()->send();
                    }
                }),

            // Actions\Action::make('resetToDraft')
            //     ->label(__('accounting::bill.reset_to_draft'))
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn (VendorBill $record): bool => $record->status === VendorBillStatus::Posted)
            //     ->form([
            //         Forms\Components\Textarea::make('reason')->label(__('accounting::bill.reason'))->required(),
            //     ])
            //     ->action(function (VendorBill $record, array $data): void {
            //         $vendorBillService = app(VendorBillService::class);
            //         try {
            //             $vendorBillService->resetToDraft($record, Auth::user(), $data['reason']);
            //             Notification::make()->title(__('accounting::bill.notification_bill_reset_success'))->success()->send();
            //         } catch (\Exception $e) {
            //             Notification::make()->title(__('accounting::bill.notification_reset_bill_error'))->body($e->getMessage())->danger()->send();
            //         }
            //     }),

            Action::make('register_payment')
                ->label(__('Register Payment'))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->modalHeading(__('Register Payment'))
                ->modalDescription(__('Register a payment for this vendor bill'))
                ->schema([
                    Select::make('journal_id')
                        ->label('Journal')
                        ->options(function (): array {
                            $tenant = Filament::getTenant();
                            if (! $tenant instanceof Company) {
                                return [];
                            }

                            return Journal::where('company_id', $tenant->getKey())
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->required()
                        ->default(function (): ?int {
                            $tenant = Filament::getTenant();
                            if (! $tenant instanceof Company) {
                                return null;
                            }

                            return Journal::where('company_id', $tenant->getKey())
                                ->where('type', 'bank')
                                ->value('id');
                        }),
                    DatePicker::make('payment_date')
                        ->label('Payment Date')
                        ->default(now())
                        ->required(),
                    MoneyInput::make('amount')
                        ->label('Amount')
                        ->currencyField('currency_id')
                        ->default(fn (VendorBill $record) => $record->getRemainingAmount())
                        ->required(),
                    TextInput::make('reference')
                        ->label('Reference')
                        ->placeholder('Optional reference'),
                    Hidden::make('currency_id')
                        ->default(fn (VendorBill $record) => $record->currency_id),
                ])
                ->action(function (VendorBill $record, array $data): void {
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
                            company_id: $record->company_id,
                            journal_id: $data['journal_id'],
                            currency_id: $record->currency_id,
                            payment_date: $data['payment_date'],
                            // settlement inferred by presence of document links
                            payment_type: PaymentType::Outbound,
                            payment_method: PaymentMethod::BankTransfer,
                            paid_to_from_partner_id: $record->vendor_id,
                            amount: Money::of($data['amount'], $currency->code),
                            document_links: [$documentLink],
                            reference: $data['reference']
                        );

                        // Create and confirm payment
                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to create payment');
                        }
                        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $user);
                        app(PaymentService::class)->confirm($payment, $user);

                        Notification::make()
                            ->title(__('Payment registered successfully'))
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('Error registering payment'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(
                    fn (VendorBill $record) => $record->status === VendorBillStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            DeleteAction::make()
                ->action(function (Model $record) {
                    if (! $record instanceof VendorBill) {
                        throw new Exception('Invalid record type');
                    }
                    app(VendorBillService::class)->delete($record);
                    $this->redirect(VendorBillResource::getUrl('index'));
                }),

            DocsAction::make('vendor-bills'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof VendorBill) {
            throw new InvalidArgumentException('Expected VendorBill record');
        }

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
                analytic_account_id: $line['analytic_account_id'] ?? null,
                shipping_cost_type: isset($line['shipping_cost_type']) ? \Modules\Foundation\Enums\ShippingCostType::tryFrom($line['shipping_cost_type']) : null,
                asset_category_id: $line['asset_category_id'] ?? null,
                deferred_start_date: $line['deferred_start_date'] ?? null,
                deferred_end_date: $line['deferred_end_date'] ?? null
            );
        }

        $vendorBillDTO = new UpdateVendorBillDTO(
            vendorBill: $record,
            company_id: $record->company_id,
            vendor_id: $data['vendor_id'],
            currency_id: $data['currency_id'],
            bill_reference: $data['bill_reference'],
            bill_date: $data['bill_date'],
            accounting_date: $data['accounting_date'],
            due_date: $data['due_date'] ?? null,
            lines: $lineDTOs,
            updated_by_user_id: (int) Auth::id(),
            incoterm: isset($data['incoterm']) ? \Modules\Foundation\Enums\Incoterm::tryFrom($data['incoterm']) : null
        );

        try {
            $updatedVendorBill = app(UpdateVendorBillAction::class)->execute($vendorBillDTO);
        } catch (\Modules\Foundation\Exceptions\UpdateNotAllowedException $e) {
            Notification::make()
                ->title(__('accounting::bill.notification_update_not_allowed'))
                ->body($e->getMessage())
                ->warning()
                ->persistent()
                ->send();

            // Halt the update process
            $this->halt();

            // This line will never be reached due to halt(), but satisfies the return type
            throw $e;
        }

        // Handle exchange_rate_at_creation separately since it's not in the DTO
        if (isset($data['exchange_rate_at_creation'])) {
            $updatedVendorBill->update([
                'exchange_rate_at_creation' => $data['exchange_rate_at_creation'],
            ]);
        }

        return $updatedVendorBill;
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

        $record = $this->getRecord();
        if (! $record instanceof VendorBill) {
            return;
        }

        // Get existing attachment file paths to avoid duplicates
        $existingPaths = $record->attachments()->pluck('file_path')->toArray();

        foreach ($this->newAttachments as $filePath) {
            // Skip if this file is already attached
            if (in_array($filePath, $existingPaths, true)) {
                continue;
            }

            if (Storage::disk('local')->exists($filePath)) {
                $fileInfo = pathinfo($filePath);
                $absolutePath = Storage::disk('local')->path($filePath);
                $mimeType = File::mimeType($absolutePath);
                $fileSize = Storage::disk('local')->size($filePath);

                VendorBillAttachment::create([
                    'company_id' => $record->company_id,
                    'vendor_bill_id' => $record->id,
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
        $record = $this->getRecord();
        if (! $record instanceof VendorBill) {
            return $data;
        }

        $record->loadMissing('lines', 'currency', 'attachments');

        $linesData = $record->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_id' => $line->tax_id,
                'expense_account_id' => $line->expense_account_id,
                'analytic_account_id' => $line->analytic_account_id,
                'deferred_start_date' => $line->deferred_start_date,
                'deferred_end_date' => $line->deferred_end_date,
            ];
        })->toArray();

        $attachmentsData = $record->attachments->pluck('file_path')->toArray();

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
