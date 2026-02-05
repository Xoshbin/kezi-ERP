<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\RelationManagers;

use Exception;
use \Filament\Actions\Action;
use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\CreateAction;
use \Filament\Actions\DeleteAction;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Kezi\Accounting\Actions\Accounting\ReverseJournalEntryAction;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\BankStatementLine;
use Kezi\Accounting\Models\JournalEntry;

/**
 * @extends RelationManager<\Kezi\Accounting\Models\BankStatement>
 */
class BankStatementLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankStatementLines';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::bank_statement.bank_statement_lines');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label(__('accounting::bank_statement.date'))
                    ->required(),
                TextInput::make('description')
                    ->label(__('accounting::bank_statement.description'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('partner_name')
                    ->label(__('accounting::bank_statement.partner_name'))
                    ->maxLength(255),
                TextInput::make('amount')
                    ->label(__('accounting::bank_statement.amount'))
                    ->required()
                    ->numeric(),
                Toggle::make('is_reconciled')
                    ->label(__('accounting::bank_statement.is_reconciled'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['journalEntry', 'bankStatement.currency']))
            ->columns([
                TextColumn::make('date')
                    ->label(__('accounting::bank_statement.date'))
                    ->date(),
                TextColumn::make('description')
                    ->label(__('accounting::bank_statement.description')),
                TextColumn::make('partner_name')
                    ->label(__('accounting::bank_statement.partner_name')),
                TextColumn::make('amount')
                    ->label(__('accounting::bank_statement.amount')),
                IconColumn::make('is_reconciled')
                    ->label(__('accounting::bank_statement.is_reconciled'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('reverse')
                    ->label(__('accounting::bank_statement.reverse_write_off'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(function (BankStatementLine $record) {
                        $je = $record->journalEntry;

                        return $record->is_reconciled && $je instanceof JournalEntry && $je->state === JournalEntryState::Posted;
                    })
                    ->authorize(function (BankStatementLine $record) {
                        $je = $record->journalEntry;

                        return $je instanceof JournalEntry && Gate::allows('reverse', $je);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('accounting::bank_statement.reverse_write_off'))
                    ->modalDescription(__('accounting::bank_statement.reverse_write_off_confirmation'))
                    ->action(function (BankStatementLine $record) {
                        try {
                            $journalEntry = $record->journalEntry;
                            if (! $journalEntry instanceof JournalEntry) {
                                throw new Exception('Journal entry not found');
                            }

                            $user = Auth::user();
                            if (! $user) {
                                throw new Exception('User must be authenticated to reverse journal entry');
                            }
                            $reverseAction = app(ReverseJournalEntryAction::class);
                            $reverseAction->execute(
                                $journalEntry,
                                __('accounting::bank_statement.write_off_reversal_description'),
                                $user
                            );

                            Notification::make()
                                ->title(__('accounting::bank_statement.write_off_reversed_successfully'))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('accounting::bank_statement.error_reversing_write_off'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
