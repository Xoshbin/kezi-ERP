<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\RelationManagers;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Accounting\Models\BankStatementLine;

class BankStatementLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankStatementLines';

    protected static ?string $title = 'Bank Statement Lines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label(__('bank_statement.date'))
                    ->required(),
                TextInput::make('description')
                    ->label(__('bank_statement.description'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('partner_name')
                    ->label(__('bank_statement.partner_name'))
                    ->maxLength(255),
                TextInput::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->required()
                    ->numeric(),
                Toggle::make('is_reconciled')
                    ->label(__('bank_statement.is_reconciled'))
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
                    ->label(__('bank_statement.date'))
                    ->date(),
                TextColumn::make('description')
                    ->label(__('bank_statement.description')),
                TextColumn::make('partner_name')
                    ->label(__('bank_statement.partner_name')),
                TextColumn::make('amount')
                    ->label(__('bank_statement.amount')),
                IconColumn::make('is_reconciled')
                    ->label(__('bank_statement.is_reconciled'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('reverse')
                    ->label('Reverse Write-Off')
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
                    ->modalHeading('Reverse Write-Off')
                    ->modalDescription('Are you sure you want to reverse this write-off? This will create a reversing journal entry and mark the bank statement line as unreconciled.')
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
                                'Bank statement line write-off reversal',
                                $user
                            );

                            Notification::make()
                                ->title('Write-off reversed successfully')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Error reversing write-off')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
