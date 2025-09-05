<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments\RelationManagers;

use App\Enums\Accounting\JournalEntryState;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\JournalEntry;
use App\Models\Payment;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JournalEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntries';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('payment.journal_entries_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payment.journal_entries_relation_manager.journal_entry_details'))
                    ->schema([
                        DatePicker::make('entry_date')
                            ->label(__('payment.journal_entries_relation_manager.entry_date'))
                            ->required()
                            ->disabled(),

                        TextInput::make('reference')
                            ->label(__('payment.journal_entries_relation_manager.reference'))
                            ->required()
                            ->disabled(),

                        Textarea::make('description')
                            ->label(__('payment.journal_entries_relation_manager.description'))
                            ->disabled()
                            ->columnSpanFull(),

                        Select::make('journal_id')
                            ->relationship('journal', 'name')
                            ->label(__('payment.journal_entries_relation_manager.journal'))
                            ->disabled(),

                        Select::make('state')
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
                TextColumn::make('entry_date')
                    ->label(__('payment.journal_entries_relation_manager.entry_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('reference')
                    ->label(__('payment.journal_entries_relation_manager.reference'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('payment.journal_entries_relation_manager.description'))
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('journal.name')
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

                TextColumn::make('state')
                    ->label(__('payment.journal_entries_relation_manager.state'))
                    ->badge()
                    ->color(fn (JournalEntryState $state): string => match ($state) {
                        JournalEntryState::Posted => 'success',
                        JournalEntryState::Reversed => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('source_type')
                    ->label(__('payment.journal_entries_relation_manager.source_type'))
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
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

                TextColumn::make('created_at')
                    ->label(__('payment.journal_entries_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('payment.journal_entries_relation_manager.filter_state'))
                    ->options(JournalEntryState::class),

                SelectFilter::make('source_type')
                    ->label(__('payment.journal_entries_relation_manager.filter_source_type'))
                    ->options([
                        'App\Models\Payment' => __('payment.journal_entries_relation_manager.source_payment'),
                        'App\Models\BankStatementLine' => __('payment.journal_entries_relation_manager.source_reconciliation'),
                        'App\Models\Invoice' => __('payment.journal_entries_relation_manager.source_invoice'),
                        'App\Models\VendorBill' => __('payment.journal_entries_relation_manager.source_vendor_bill'),
                    ]),
            ])
            ->recordActions([
                // View action removed for now - can be added when proper routes are configured
            ])
            ->defaultSort('entry_date', 'desc')
            ->emptyStateHeading(__('payment.journal_entries_relation_manager.no_journal_entries'))
            ->emptyStateDescription(__('payment.journal_entries_relation_manager.no_journal_entries_description'));
    }

    /**
     * Modify the query to get all journal entries related to this payment.
     * This includes both the direct journal entry and any polymorphic entries.
     *
     * @return Builder<JournalEntry>
     */
    protected function getTableQuery(): Builder
    {
        $payment = $this->getOwnerRecord();
        if (! $payment instanceof Payment) {
            return JournalEntry::query()->whereRaw('1 = 0');
        }

        return JournalEntry::query()
            ->where(function (Builder $query) use ($payment) {
                // Direct journal entry relationship
                $query->where('id', $payment->journal_entry_id)
                    // Polymorphic relationship (reconciliation entries, etc.)
                    ->orWhere(function (Builder $subQuery) use ($payment) {
                        $subQuery->where('source_type', Payment::class)
                            ->where('source_id', $payment->getKey());
                    });
            })
            ->whereNotNull('id'); // Ensure we don't get null results
    }
}
