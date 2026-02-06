<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;
use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Kezi\Inventory\Models\AdjustmentDocument;
use Kezi\Purchase\Models\VendorBill;

/**
 * @extends RelationManager<\Kezi\Purchase\Models\VendorBill>
 */
class AdjustmentDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'adjustmentDocuments';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::bill.adjustment_documents_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::bill.adjustment_documents_relation_manager.document_details'))
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.company'))
                            ->required()
                            ->default(function (): ?int {
                                $owner = $this->getOwnerRecord();

                                return $owner instanceof VendorBill ? $owner->company_id : null;
                            }),

                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.currency'))
                            ->required()
                            ->default(function (): ?int {
                                $owner = $this->getOwnerRecord();

                                return $owner instanceof VendorBill ? $owner->currency_id : null;
                            }),

                        Select::make('type')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.type'))
                            ->options([
                                AdjustmentDocumentType::DebitNote->value => AdjustmentDocumentType::DebitNote->label(),
                                AdjustmentDocumentType::Miscellaneous->value => AdjustmentDocumentType::Miscellaneous->label(),
                            ])
                            ->required()
                            ->default(AdjustmentDocumentType::DebitNote->value),

                        DatePicker::make('date')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.date'))
                            ->required()
                            ->default(now()),

                        TextInput::make('reference_number')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.reference_number'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., DN-2024-001'),

                        Select::make('status')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.status'))
                            ->options([
                                AdjustmentDocumentStatus::Draft->value => AdjustmentDocumentStatus::Draft->label(),
                                AdjustmentDocumentStatus::Posted->value => AdjustmentDocumentStatus::Posted->label(),
                                AdjustmentDocumentStatus::Cancelled->value => AdjustmentDocumentStatus::Cancelled->label(),
                            ])
                            ->required()
                            ->default(AdjustmentDocumentStatus::Draft->value),

                        Textarea::make('reason')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.reason'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder(__('accounting::bill.adjustment_documents_relation_manager.reason_placeholder')),

                        \Kezi\Foundation\Filament\Forms\Components\MoneyInput::make('subtotal')
                            ->label(__('accounting::bill.adjustment_documents_relation_manager.amount'))
                            ->required()
                            ->currencyField('currency_id'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.reference_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.type'))
                    ->formatStateUsing(fn (AdjustmentDocumentType $state): string => $state->label())
                    ->badge()
                    ->color(fn (AdjustmentDocumentType $state): string => match ($state) {
                        AdjustmentDocumentType::CreditNote => 'success',
                        AdjustmentDocumentType::DebitNote => 'warning',
                        AdjustmentDocumentType::Miscellaneous => 'info',
                    }),

                TextColumn::make('date')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.date'))
                    ->date()
                    ->sortable(),

                MoneyColumn::make('total_amount')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.total_amount'))
                    ->sortable(),

                MoneyColumn::make('total_tax')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.total_tax'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.status'))
                    ->formatStateUsing(fn (AdjustmentDocumentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (AdjustmentDocumentStatus $state): string => match ($state) {
                        AdjustmentDocumentStatus::Draft => 'gray',
                        AdjustmentDocumentStatus::Posted => 'success',
                        AdjustmentDocumentStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('reason')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.reason'))
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('journalEntry.id')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.journal_entry'))
                    ->placeholder(__('accounting::bill.adjustment_documents_relation_manager.no_journal_entry'))
                    ->toggleable(),

                TextColumn::make('posted_at')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.filter_type'))
                    ->options([
                        AdjustmentDocumentType::DebitNote->value => AdjustmentDocumentType::DebitNote->label(),
                        AdjustmentDocumentType::Miscellaneous->value => AdjustmentDocumentType::Miscellaneous->label(),
                    ]),

                SelectFilter::make('status')
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.filter_status'))
                    ->options([
                        AdjustmentDocumentStatus::Draft->value => AdjustmentDocumentStatus::Draft->label(),
                        AdjustmentDocumentStatus::Posted->value => AdjustmentDocumentStatus::Posted->label(),
                        AdjustmentDocumentStatus::Cancelled->value => AdjustmentDocumentStatus::Cancelled->label(),
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('accounting::bill.adjustment_documents_relation_manager.create_adjustment'))
                    ->using(function (array $data, string $model): Model {
                        /** @var VendorBill $owner */
                        $owner = $this->getOwnerRecord();

                        $currency = \Kezi\Foundation\Models\Currency::find($data['currency_id']);

                        // Create a single line DTO for the adjustment amount
                        // We need an account ID. For now, we'll fetch a default expense/adjustment account
                        // In a real scenario, this might need to be selected by the user.
                        // Let's try to find a suitable account or fallback to the first available expense account.
                        $account = \Kezi\Accounting\Models\Account::where('code', 'like', '5%')->first(); // Expense account

                        $lineDto = new \Kezi\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO(
                            description: $data['reason'],
                            quantity: 1,
                            unit_price: \Brick\Money\Money::of($data['subtotal'], $currency->code),
                            account_id: $account?->id ?? 1, // Fallback if no account found
                            product_id: null,
                            tax_id: null,
                        );

                        $dto = new \Kezi\Inventory\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO(
                            company_id: (int) \Filament\Facades\Filament::getTenant()->id,
                            type: \Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType::from($data['type']),
                            date: $data['date'],
                            reference_number: $data['reference_number'],
                            reason: $data['reason'],
                            currency_id: (int) $data['currency_id'],
                            original_invoice_id: null,
                            original_vendor_bill_id: $owner instanceof VendorBill ? $owner->getKey() : null,
                            lines: [$lineDto],
                        );

                        return app(\Kezi\Inventory\Actions\Adjustments\CreateAdjustmentDocumentAction::class)->execute($dto);
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft),
                DeleteAction::make()
                    ->visible(fn (AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => true), // Add custom logic if needed
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
