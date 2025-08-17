<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use App\Enums\Adjustments\AdjustmentDocumentType;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\AdjustmentDocument;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdjustmentDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'adjustmentDocuments';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('invoice.adjustment_documents_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('invoice.adjustment_documents_relation_manager.document_details'))
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label(__('invoice.adjustment_documents_relation_manager.company'))
                            ->required()
                            ->default(fn() => $this->getOwnerRecord()->company_id),

                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->label(__('invoice.adjustment_documents_relation_manager.currency'))
                            ->required()
                            ->default(fn() => $this->getOwnerRecord()->currency_id),

                        Select::make('type')
                            ->label(__('invoice.adjustment_documents_relation_manager.type'))
                            ->options([
                                AdjustmentDocumentType::CreditNote->value => AdjustmentDocumentType::CreditNote->label(),
                                AdjustmentDocumentType::Miscellaneous->value => AdjustmentDocumentType::Miscellaneous->label(),
                            ])
                            ->required()
                            ->default(AdjustmentDocumentType::CreditNote->value),

                        DatePicker::make('date')
                            ->label(__('invoice.adjustment_documents_relation_manager.date'))
                            ->required()
                            ->default(now()),

                        TextInput::make('reference_number')
                            ->label(__('invoice.adjustment_documents_relation_manager.reference_number'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., CN-2024-001'),

                        Select::make('status')
                            ->label(__('invoice.adjustment_documents_relation_manager.status'))
                            ->options([
                                AdjustmentDocumentStatus::Draft->value => AdjustmentDocumentStatus::Draft->label(),
                                AdjustmentDocumentStatus::Posted->value => AdjustmentDocumentStatus::Posted->label(),
                                AdjustmentDocumentStatus::Cancelled->value => AdjustmentDocumentStatus::Cancelled->label(),
                            ])
                            ->required()
                            ->default(AdjustmentDocumentStatus::Draft->value),

                        Textarea::make('reason')
                            ->label(__('invoice.adjustment_documents_relation_manager.reason'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder(__('invoice.adjustment_documents_relation_manager.reason_placeholder')),
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
                    ->label(__('invoice.adjustment_documents_relation_manager.reference_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('invoice.adjustment_documents_relation_manager.type'))
                    ->formatStateUsing(fn(AdjustmentDocumentType $state): string => $state->label())
                    ->badge()
                    ->color(fn(AdjustmentDocumentType $state): string => match($state) {
                        AdjustmentDocumentType::CreditNote => 'success',
                        AdjustmentDocumentType::DebitNote => 'warning',
                        AdjustmentDocumentType::Miscellaneous => 'info',
                    }),

                TextColumn::make('date')
                    ->label(__('invoice.adjustment_documents_relation_manager.date'))
                    ->date()
                    ->sortable(),

                MoneyColumn::make('total_amount')
                    ->label(__('invoice.adjustment_documents_relation_manager.total_amount'))
                    ->sortable(),

                MoneyColumn::make('total_tax')
                    ->label(__('invoice.adjustment_documents_relation_manager.total_tax'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('invoice.adjustment_documents_relation_manager.status'))
                    ->formatStateUsing(fn(AdjustmentDocumentStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn(AdjustmentDocumentStatus $state): string => match($state) {
                        AdjustmentDocumentStatus::Draft => 'gray',
                        AdjustmentDocumentStatus::Posted => 'success',
                        AdjustmentDocumentStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('reason')
                    ->label(__('invoice.adjustment_documents_relation_manager.reason'))
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('journalEntry.id')
                    ->label(__('invoice.adjustment_documents_relation_manager.journal_entry'))
                    ->placeholder(__('invoice.adjustment_documents_relation_manager.no_journal_entry'))
                    ->toggleable(),

                TextColumn::make('posted_at')
                    ->label(__('invoice.adjustment_documents_relation_manager.posted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('invoice.adjustment_documents_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('invoice.adjustment_documents_relation_manager.filter_type'))
                    ->options([
                        AdjustmentDocumentType::CreditNote->value => AdjustmentDocumentType::CreditNote->label(),
                        AdjustmentDocumentType::Miscellaneous->value => AdjustmentDocumentType::Miscellaneous->label(),
                    ]),

                SelectFilter::make('status')
                    ->label(__('invoice.adjustment_documents_relation_manager.filter_status'))
                    ->options([
                        AdjustmentDocumentStatus::Draft->value => AdjustmentDocumentStatus::Draft->label(),
                        AdjustmentDocumentStatus::Posted->value => AdjustmentDocumentStatus::Posted->label(),
                        AdjustmentDocumentStatus::Cancelled->value => AdjustmentDocumentStatus::Cancelled->label(),
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('invoice.adjustment_documents_relation_manager.create_adjustment'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['original_invoice_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft),
                DeleteAction::make()
                    ->visible(fn(AdjustmentDocument $record): bool => $record->status === AdjustmentDocumentStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => true), // Add custom logic if needed
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
