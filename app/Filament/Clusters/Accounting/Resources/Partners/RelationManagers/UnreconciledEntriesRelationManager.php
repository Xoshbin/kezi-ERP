<?php

namespace App\Filament\Clusters\Accounting\Resources\Partners\RelationManagers;

use Filament\Facades\Filament;
use Exception;
use App\Actions\Reconciliation\MatchJournalItemsAction;
use App\Enums\Reconciliation\ReconciliationType;
use App\Exceptions\Reconciliation\ReconciliationException;
use App\Models\JournalEntryLine;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UnreconciledEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'journalEntryLines';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('partner.unreconciled_entries_relation_manager.title');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function canCreate(): bool
    {
        return false; // Journal entry lines are created through other processes
    }

    public function canEdit(Model $record): bool
    {
        return false; // Journal entry lines should not be edited directly
    }

    public function canDelete(Model $record): bool
    {
        return false; // Journal entry lines should not be deleted directly
    }

    /**
     * Check if the reconciliation feature is enabled for the current company.
     */
    protected function isReconciliationEnabled(): bool
    {
        $company = Filament::getTenant();
        return $company && $company->enable_reconciliation;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // No form needed as we don't create/edit journal entry lines here
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $this->modifyQuery($query))
            ->columns([
                TextColumn::make('journalEntry.entry_date')
                    ->label(__('partner.unreconciled_entries_relation_manager.entry_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('journalEntry.reference')
                    ->label(__('partner.unreconciled_entries_relation_manager.reference'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account.code')
                    ->label(__('partner.unreconciled_entries_relation_manager.account_code'))
                    ->searchable(),
                TextColumn::make('account.name')
                    ->label(__('partner.unreconciled_entries_relation_manager.account_name'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('partner.unreconciled_entries_relation_manager.description'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('debit')
                    ->label(__('partner.unreconciled_entries_relation_manager.debit'))
                    ->money(fn (JournalEntryLine $record) => $record->journalEntry->company->currency->code)
                    ->alignEnd(),
                TextColumn::make('credit')
                    ->label(__('partner.unreconciled_entries_relation_manager.credit'))
                    ->money(fn (JournalEntryLine $record) => $record->journalEntry->company->currency->code)
                    ->alignEnd(),
            ])
            ->filters([
                // Add filters if needed
            ])
            ->headerActions([
                Action::make('reconcile_selected')
                    ->label(__('partner.unreconciled_entries_relation_manager.reconcile_selected'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => $this->isReconciliationEnabled())
                    ->requiresConfirmation()
                    ->modalHeading(__('partner.unreconciled_entries_relation_manager.reconcile_modal_heading'))
                    ->modalDescription(__('partner.unreconciled_entries_relation_manager.reconcile_modal_description'))
                    ->schema([
                        TextInput::make('reference')
                            ->label(__('partner.unreconciled_entries_relation_manager.reconcile_reference'))
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('partner.unreconciled_entries_relation_manager.reconcile_description'))
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $this->reconcileSelectedEntries($data);
                    })
                    ->disabled(fn () => !$this->hasSelectedRecords()),
            ])
            ->toolbarActions([
                BulkAction::make('reconcile')
                    ->label(__('partner.unreconciled_entries_relation_manager.reconcile'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => $this->isReconciliationEnabled())
                    ->requiresConfirmation()
                    ->modalHeading(__('partner.unreconciled_entries_relation_manager.reconcile_modal_heading'))
                    ->modalDescription(__('partner.unreconciled_entries_relation_manager.reconcile_modal_description'))
                    ->schema([
                        TextInput::make('reference')
                            ->label(__('partner.unreconciled_entries_relation_manager.reconcile_reference'))
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('partner.unreconciled_entries_relation_manager.reconcile_description'))
                            ->rows(3),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $this->reconcileEntries($records, $data);
                    }),
            ])
            ->emptyStateHeading(__('partner.unreconciled_entries_relation_manager.empty_state_heading'))
            ->emptyStateDescription(__('partner.unreconciled_entries_relation_manager.empty_state_description'))
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Modify the query to show only unreconciled entries from reconcilable accounts.
     */
    protected function modifyQuery(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('reconciliations') // Only unreconciled entries
            ->whereHas('account', function (Builder $query) {
                $query->where('allow_reconciliation', true); // Only reconcilable accounts
            })
            ->whereHas('journalEntry', function (Builder $query) {
                $query->where('is_posted', true); // Only posted entries
            })
            ->with([
                'journalEntry.company.currency',
                'account',
                'reconciliations'
            ])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if there are selected records for the header action.
     */
    protected function hasSelectedRecords(): bool
    {
        // This would need to be implemented based on Filament's selection state
        // For now, we'll rely on the bulk action which handles selection automatically
        return false;
    }

    /**
     * Reconcile the selected entries.
     */
    protected function reconcileSelectedEntries(array $data): void
    {
        // This would be called from the header action
        // Implementation would depend on how we track selected records
        Notification::make()
            ->title(__('partner.unreconciled_entries_relation_manager.use_bulk_action'))
            ->warning()
            ->send();
    }

    /**
     * Reconcile the given journal entry lines.
     */
    protected function reconcileEntries(Collection $records, array $data): void
    {
        try {
            DB::transaction(function () use ($records, $data) {
                $action = app(MatchJournalItemsAction::class);

                $reconciliation = $action->execute(
                    journalLineIds: $records->pluck('id')->toArray(),
                    reconciliationType: ReconciliationType::ManualArAp,
                    reference: $data['reference'] ?? null,
                    description: $data['description'] ?? null
                );

                Notification::make()
                    ->title(__('partner.unreconciled_entries_relation_manager.reconciliation_success'))
                    ->body(__('partner.unreconciled_entries_relation_manager.reconciliation_success_body', [
                        'count' => $records->count(),
                        'reference' => $reconciliation->reference ?? $reconciliation->id,
                    ]))
                    ->success()
                    ->send();
            });

            // Refresh the table to remove reconciled entries
            $this->resetTable();

        } catch (ReconciliationException $e) {
            Notification::make()
                ->title(__('partner.unreconciled_entries_relation_manager.reconciliation_error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title(__('partner.unreconciled_entries_relation_manager.reconciliation_error'))
                ->body(__('partner.unreconciled_entries_relation_manager.reconciliation_error_generic'))
                ->danger()
                ->send();
        }
    }
}
