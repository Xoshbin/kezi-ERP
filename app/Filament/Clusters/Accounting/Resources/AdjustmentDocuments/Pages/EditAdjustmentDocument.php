<?php
// in app/Filament/Resources/AdjustmentDocumentResource/Pages/EditAdjustmentDocument.php

namespace App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages;

// Add these imports
use App\Actions\Adjustments\UpdateAdjustmentDocumentAction;
use App\DataTransferObjects\Adjustments\UpdateAdjustmentDocumentDTO;
use App\DataTransferObjects\Adjustments\UpdateAdjustmentDocumentLineDTO;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\AdjustmentDocumentResource;
use App\Models\AdjustmentDocument;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Services\AdjustmentDocumentService;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

// Other use statements...

class EditAdjustmentDocument extends EditRecord
{
    protected static string $resource = AdjustmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        // This action's call to $this->save() is what triggers the error lifecycle.
        return [
            Action::make('preview_posting')
                ->label(__('Preview Posting'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading(__('Posting Preview'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalWidth('7xl')
                ->modalContent(function (AdjustmentDocument $record) {
                    $preview = app(\App\Actions\Accounting\BuildAdjustmentPostingPreviewAction::class)->execute($record);
                    return view('filament/accounting/adjustments/preview-posting', [
                        'preview' => $preview,
                        'adjustment' => $record,
                    ]);
                }),
            Action::make('export_preview_csv')
                ->label(__('Export Preview (CSV)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft)
                ->action(function (AdjustmentDocument $record) {
                    $preview = app(\App\Actions\Accounting\BuildAdjustmentPostingPreviewAction::class)->execute($record);
                    $rows = [];
                    $rows[] = ['Account Code', 'Account Name', 'Description', 'Debit', 'Credit'];
                    foreach ($preview['lines'] as $l) {
                        $rows[] = [
                            is_array($l['account_code'] ?? null) ? (string) (reset($l['account_code']) ?: '') : (string) ($l['account_code'] ?? ''),
                            is_array($l['account_name'] ?? null) ? (string) (reset($l['account_name']) ?: '') : (string) ($l['account_name'] ?? ''),
                            is_array($l['description'] ?? null) ? (string) (reset($l['description']) ?: '') : (string) ($l['description'] ?? ''),
                            number_format(($l['debit_minor'] ?? 0) / 100, 2, '.', ''),
                            number_format(($l['credit_minor'] ?? 0) / 100, 2, '.', ''),
                        ];
                    }
                    $csv = '';
                    foreach ($rows as $row) {
                        $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\n";
                    }
                    $filename = 'adjustment-' . ($record->reference_number ?: ('ADJ-' . str_pad($record->id, 5, '0', STR_PAD_LEFT))) . '-preview.csv';
                    return response()->streamDownload(function () use ($csv) { echo $csv; }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
            Action::make('export_preview_pdf')
                ->label(__('Export Preview (PDF)'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft)
                ->action(function (AdjustmentDocument $record) {
                    $preview = app(\App\Actions\Accounting\BuildAdjustmentPostingPreviewAction::class)->execute($record);
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament/accounting/adjustments/preview-posting-pdf', [
                        'preview' => $preview,
                        'adjustment' => $record,
                    ]);
                    $filename = 'adjustment-' . ($record->reference_number ?: ('ADJ-' . str_pad($record->id, 5, '0', STR_PAD_LEFT))) . '-preview.pdf';
                    return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
            Action::make('post')
                ->label(__('adjustment_document.post_document'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft)
                ->action(function (AdjustmentDocument $record): void {
                    $this->save(); // This triggers mutateFormDataBeforeSave -> handleRecordUpdate
                    $service = app(AdjustmentDocumentService::class);
                    try {
                        $service->post($record, auth()->user());
                        Notification::make()->title(__('adjustment_document.notification_document_posted_successfully'))->success()->send();
                    } catch (Exception $e) {
                        Notification::make()->title(__('adjustment_document.notification_document_post_error'))->body($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    // --- START OF THE FIX ---
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 1. Forcefully derive currency_id if it's missing from the form submission.
        if (empty($data['currency_id'])) {
            if (!empty($data['original_invoice_id'])) {
                $data['currency_id'] = Invoice::find($data['original_invoice_id'])?->currency_id;
            } elseif (!empty($data['original_vendor_bill_id'])) {
                $data['currency_id'] = VendorBill::find($data['original_vendor_bill_id'])?->currency_id;
            }
        }

        // 2. As a final fallback, get it from the original record being edited.
        if (empty($data['currency_id'])) {
            $data['currency_id'] = $this->record->currency_id;
        }

        // 3. If it's *still* missing, stop with a clean validation error.
        if (empty($data['currency_id'])) {
            throw ValidationException::withMessages([
                'data.currency_id' => __('validation.required', ['attribute' => 'currency']),
            ]);
        }

        // 4. Inject the now-guaranteed currency_id into the line items.
        $parentCurrencyId = $data['currency_id'];
        if (isset($data['lines'])) {
            $mutatedLines = [];
            foreach ($data['lines'] as $line) {
                $line['currency_id'] = $parentCurrencyId;
                $mutatedLines[] = $line;
            }
            $data['lines'] = $mutatedLines;
        }

        return $data;
    }
    // --- END OF THE FIX ---

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('lines');

        // Keep Money objects for MoneyInput components
        $linesData = $this->record->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price, // Keep as Money object
                'tax_id' => $line->tax_id,
                'account_id' => $line->account_id,
            ];
        })->toArray();

        $data['lines'] = $linesData;
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // This method will now always receive a valid $data['currency_id']
        $currency = Currency::find($data['currency_id']);
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new UpdateAdjustmentDocumentLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $currency->code),
                account_id: $line['account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null
            );
        }

        $dto = new UpdateAdjustmentDocumentDTO(
            adjustmentDocument: $record,
            type: AdjustmentDocumentType::from($data['type']),
            date: $data['date'],
            reference_number: $data['reference_number'],
            reason: $data['reason'],
            currency_id: $data['currency_id'], // This line will no longer cause an error
            original_invoice_id: $data['original_invoice_id'] ?? null,
            original_vendor_bill_id: $data['original_vendor_bill_id'] ?? null,
            lines: $lineDTOs
        );

        return app(UpdateAdjustmentDocumentAction::class)->execute($dto);
    }
}
