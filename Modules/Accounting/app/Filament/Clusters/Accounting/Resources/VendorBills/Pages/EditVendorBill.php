<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Actions\Accounting\BuildVendorBillPostingPreviewAction;
use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\DataTransferObjects\Purchases\VendorBillLineDTO;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentType;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Widgets\SettlementSummaryWidget;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Company;
use App\Models\Journal;
use App\Models\VendorBillAttachment;
use App\Services\PaymentService;
use App\Services\VendorBillService;
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
use Illuminate\Support\Facades\Storage;

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
                ->visible(fn (\Modules\Purchase\Models\VendorBill $record): bool => $record->status === VendorBillStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading(__('Posting Preview'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalWidth('7xl')
                ->modalContent(function (\Modules\Purchase\Models\VendorBill $record) {
                    $preview = app(BuildVendorBillPostingPreviewAction::class)->execute($record);

                    return view('filament/accounting/vendor-bills/preview-posting', [
                        'preview' => $preview,
                        'bill' => $record,
                    ]);
                }),

            Action::make('export_preview_csv')
                ->label(__('Export Preview (CSV)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (\Modules\Purchase\Models\VendorBill $record): bool => $record->status === VendorBillStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (\Modules\Purchase\Models\VendorBill $record): \Symfony\Component\HttpFoundation\StreamedResponse {
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
                ->visible(fn (\Modules\Purchase\Models\VendorBill $record): bool => $record->status === VendorBillStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (\Modules\Purchase\Models\VendorBill $record): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $preview = app(BuildVendorBillPostingPreviewAction::class)->execute($record);
                    $pdf = Pdf::loadView('filament/accounting/vendor-bills/preview-posting-pdf', [
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

            Action::make('confirm')
                ->label(__('vendor_bill.confirm'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (\Modules\Purchase\Models\VendorBill $record): bool => $record->status === VendorBillStatus::Draft)
                ->disabled(fn (\Modules\Purchase\Models\VendorBill $record): bool => $record->lines->isEmpty() || $record->total_amount->isZero())
                ->action(function (\Modules\Purchase\Models\VendorBill $record): void {
                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $user = Auth::user();
                        if (! $user) {
                            throw new \Exception('User must be authenticated to confirm vendor bill');
                        }
                        $vendorBillService->confirm($record, $user);
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
                    \Modules\Foundation\App\Filament\Forms\Components\MoneyInput::make('amount')
                        ->label('Amount')
                        ->currencyField('currency_id')
                        ->default(fn (\Modules\Purchase\Models\VendorBill $record) => $record->getRemainingAmount())
                        ->required(),
                    TextInput::make('reference')
                        ->label('Reference')
                        ->placeholder('Optional reference'),
                    Hidden::make('currency_id')
                        ->default(fn (\Modules\Purchase\Models\VendorBill $record) => $record->currency_id),
                ])
                ->action(function (\Modules\Purchase\Models\VendorBill $record, array $data): void {
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
                            throw new \Exception('User must be authenticated to create payment');
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
                ->visible(fn (\Modules\Purchase\Models\VendorBill $record) => $record->status === VendorBillStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            DeleteAction::make()
                ->action(function (Model $record) {
                    if (! $record instanceof \Modules\Purchase\Models\VendorBill) {
                        throw new \Exception('Invalid record type');
                    }
                    app(VendorBillService::class)->delete($record);
                    $this->redirect(VendorBillResource::getUrl('index'));
                }),

            \Modules\Foundation\App\Filament\Actions\DocsAction::make('vendor-bills'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof \Modules\Purchase\Models\VendorBill) {
            throw new \InvalidArgumentException('Expected VendorBill record');
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
                analytic_account_id: $line['analytic_account_id'] ?? null
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
            updated_by_user_id: (int) Auth::id()
        );

        $updatedVendorBill = app(UpdateVendorBillAction::class)->execute($vendorBillDTO);

        // Handle exchange_rate_at_creation separately since it's not in the DTO
        if (isset($data['exchange_rate_at_creation'])) {
            $updatedVendorBill->update([
                'exchange_rate_at_creation' => $data['exchange_rate_at_creation']
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
        if (! $record instanceof \Modules\Purchase\Models\VendorBill) {
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
                $mimeType = \Illuminate\Support\Facades\File::mimeType($absolutePath);
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
        if (! $record instanceof \Modules\Purchase\Models\VendorBill) {
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
