<?php

namespace App\Filament\Resources\BankStatementResource\RelationManagers;

use App\Actions\Accounting\ReverseJournalEntryAction;
use App\Enums\Accounting\JournalEntryState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class BankStatementLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankStatementLines';

    protected static ?string $title = 'Bank Statement Lines';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label(__('bank_statement.date'))
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label(__('bank_statement.description'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('partner_name')
                    ->label(__('bank_statement.partner_name'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_reconciled')
                    ->label(__('bank_statement.is_reconciled'))
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute(null)
            ->modifyQueryUsing(fn (Builder $query) => $query->with('journalEntry'))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('bank_statement.date'))
                    ->date(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('bank_statement.description')),
                Tables\Columns\TextColumn::make('partner_name')
                    ->label(__('bank_statement.partner_name')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('bank_statement.amount')),
                Tables\Columns\IconColumn::make('is_reconciled')
                    ->label(__('bank_statement.is_reconciled'))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse Write-Off')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_reconciled && $record->journalEntry?->state === JournalEntryState::Posted)
                    ->authorize(fn ($record) => Gate::allows('reverse', $record->journalEntry))
                    ->requiresConfirmation()
                    ->modalHeading('Reverse Write-Off')
                    ->modalDescription('Are you sure you want to reverse this write-off? This will create a reversing journal entry and mark the bank statement line as unreconciled.')
                    ->action(function ($record) {
                        try {
                            $reverseAction = app(ReverseJournalEntryAction::class);
                            $reverseAction->execute($record->journalEntry);

                            Notification::make()
                                ->title('Write-off reversed successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error reversing write-off')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
