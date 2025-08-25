<?php

namespace App\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\DataTransferObjects\Purchases\VendorBillLineDTO;
use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Widgets\SettlementSummaryWidget;
use App\Models\VendorBill;
use App\Models\VendorBillAttachment;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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
