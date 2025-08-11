<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Enums\Accounting\JournalEntryState;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntries';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('payment.journal_entries_relation_manager.title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('payment.journal_entries_relation_manager.journal_entry_details'))
                    ->schema([
                        Forms\Components\DatePicker::make('entry_date')
                            ->label(__('payment.journal_entries_relation_manager.entry_date'))
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('reference')
                            ->label(__('payment.journal_entries_relation_manager.reference'))
                            ->required()
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('payment.journal_entries_relation_manager.description'))
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('journal_id')
                            ->relationship('journal', 'name')
                            ->label(__('payment.journal_entries_relation_manager.journal'))
                            ->disabled(),

                        Forms\Components\Select::make('state')
                            ->label(__('payment.journal_entries_relation_manager.state'))
                            ->options(JournalEntryState::class)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label(__('payment.journal_entries_relation_manager.entry_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('payment.journal_entries_relation_manager.reference'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('payment.journal_entries_relation_manager.description'))
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('journal.name')
                    ->label(__('payment.journal_entries_relation_manager.journal'))
                    ->toggleable(),

                MoneyColumn::make('total_debit')
                    ->label(__('payment.journal_entries_relation_manager.total_debit'))
                    ->sortable()
                    ->toggleable(),

                MoneyColumn::make('total_credit')
                    ->label(__('payment.journal_entries_relation_manager.total_credit'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('state')
                    ->label(__('payment.journal_entries_relation_manager.state'))
                    ->badge()
                    ->color(fn (JournalEntryState $state): string => match ($state) {
                        JournalEntryState::Posted => 'success',
                        JournalEntryState::Reversed => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source_type')
                    ->label(__('payment.journal_entries_relation_manager.source_type'))
                    ->formatStateUsing(function (?string $state): string {
                        if (!$state) {
                            return __('payment.journal_entries_relation_manager.no_source');
                        }

                        return match ($state) {
                            'App\Models\Payment' => __('payment.journal_entries_relation_manager.source_payment'),
                            'App\Models\BankStatementLine' => __('payment.journal_entries_relation_manager.source_reconciliation'),
                            'App\Models\Invoice' => __('payment.journal_entries_relation_manager.source_invoice'),
                            'App\Models\VendorBill' => __('payment.journal_entries_relation_manager.source_vendor_bill'),
                            default => class_basename($state),
                        };
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'App\Models\Payment' => 'primary',
                        'App\Models\BankStatementLine' => 'warning',
                        'App\Models\Invoice' => 'info',
                        'App\Models\VendorBill' => 'secondary',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('payment.journal_entries_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->label(__('payment.journal_entries_relation_manager.filter_state'))
                    ->options(JournalEntryState::class),

                Tables\Filters\SelectFilter::make('source_type')
                    ->label(__('payment.journal_entries_relation_manager.filter_source_type'))
                    ->options([
                        'App\Models\Payment' => __('payment.journal_entries_relation_manager.source_payment'),
                        'App\Models\BankStatementLine' => __('payment.journal_entries_relation_manager.source_reconciliation'),
                        'App\Models\Invoice' => __('payment.journal_entries_relation_manager.source_invoice'),
                        'App\Models\VendorBill' => __('payment.journal_entries_relation_manager.source_vendor_bill'),
                    ]),
            ])
            ->actions([
                // View action removed for now - can be added when proper routes are configured
            ])
            ->defaultSort('entry_date', 'desc')
            ->emptyStateHeading(__('payment.journal_entries_relation_manager.no_journal_entries'))
            ->emptyStateDescription(__('payment.journal_entries_relation_manager.no_journal_entries_description'));
    }

    /**
     * Modify the query to get all journal entries related to this payment.
     * This includes both the direct journal entry and any polymorphic entries.
     */
    protected function getTableQuery(): Builder
    {
        $payment = $this->getOwnerRecord();

        return JournalEntry::query()
            ->where(function (Builder $query) use ($payment) {
                // Direct journal entry relationship
                $query->where('id', $payment->journal_entry_id)
                    // Polymorphic relationship (reconciliation entries, etc.)
                    ->orWhere(function (Builder $subQuery) use ($payment) {
                        $subQuery->where('source_type', get_class($payment))
                            ->where('source_id', $payment->id);
                    });
            })
            ->whereNotNull('id'); // Ensure we don't get null results
    }
}
