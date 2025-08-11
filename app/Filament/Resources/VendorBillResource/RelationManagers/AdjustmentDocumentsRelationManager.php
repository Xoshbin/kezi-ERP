<?php

namespace App\Filament\Resources\VendorBillResource\RelationManagers;

use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\AdjustmentDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdjustmentDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'adjustmentDocuments';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('vendor_bill.adjustment_documents_relation_manager.title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('vendor_bill.adjustment_documents_relation_manager.document_details'))
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.company'))
                            ->required()
                            ->default(fn() => $this->getOwnerRecord()->company_id),

                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.currency'))
                            ->required()
                            ->default(fn() => $this->getOwnerRecord()->currency_id),

                        Forms\Components\Select::make('type')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.type'))
                            ->options([
                                AdjustmentDocumentType::DebitNote->value => AdjustmentDocumentType::DebitNote->label(),
                                AdjustmentDocumentType::Miscellaneous->value => AdjustmentDocumentType::Miscellaneous->label(),
                            ])
                            ->required()
                            ->default(AdjustmentDocumentType::DebitNote->value),

                        Forms\Components\DatePicker::make('date')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.date'))
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('reference_number')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.reference_number'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., DN-2024-001'),

                        Forms\Components\Select::make('status')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.status'))
                            ->options([
                                AdjustmentDocumentStatus::Draft->value => AdjustmentDocumentStatus::Draft->label(),
                                AdjustmentDocumentStatus::Posted->value => AdjustmentDocumentStatus::Posted->label(),
                                AdjustmentDocumentStatus::Cancelled->value => AdjustmentDocumentStatus::Cancelled->label(),
                            ])
                            ->required()
                            ->default(AdjustmentDocumentStatus::Draft->value),

                        Forms\Components\Textarea::make('reason')
                            ->label(__('vendor_bill.adjustment_documents_relation_manager.reason'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder(__('vendor_bill.adjustment_documents_relation_manager.reason_placeholder')),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.reference_number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.type'))
                    ->formatStateUsing(fn(AdjustmentDocumentType $state): string => $state->label())
                    ->badge()
                    ->color(fn(AdjustmentDocumentType $state): string => match($state) {
                        AdjustmentDocumentType::CreditNote => 'success',
                        AdjustmentDocumentType::DebitNote => 'warning',
                        AdjustmentDocumentType::Miscellaneous => 'info',
                    }),

                Tables\Columns\TextColumn::make('date')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.date'))
                    ->date()
                    ->sortable(),

                MoneyColumn::make('total_amount')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.total_amount'))
                    ->sortable(),

                MoneyColumn::make('total_tax')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.total_tax'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.status'))
                    ->formatStateUsing(fn(AdjustmentDocumentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(AdjustmentDocumentStatus $state): string => match($state) {
                        AdjustmentDocumentStatus::Draft => 'gray',
                        AdjustmentDocumentStatus::Posted => 'success',
                        AdjustmentDocumentStatus::Cancelled => 'danger',
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.reason'))
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('journalEntry.id')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.journal_entry'))
                    ->placeholder(__('vendor_bill.adjustment_documents_relation_manager.no_journal_entry'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('posted_at')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.filter_type'))
                    ->options([
                        AdjustmentDocumentType::DebitNote->value => AdjustmentDocumentType::DebitNote->label(),
                        AdjustmentDocumentType::Miscellaneous->value => AdjustmentDocumentType::Miscellaneous->label(),
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.filter_status'))
                    ->options([
                        AdjustmentDocumentStatus::Draft->value => AdjustmentDocumentStatus::Draft->label(),
                        AdjustmentDocumentStatus::Posted->value => AdjustmentDocumentStatus::Posted->label(),
                        AdjustmentDocumentStatus::Cancelled->value => AdjustmentDocumentStatus::Cancelled->label(),
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('vendor_bill.adjustment_documents_relation_manager.create_adjustment'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['original_vendor_bill_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => true), // Add custom logic if needed
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
