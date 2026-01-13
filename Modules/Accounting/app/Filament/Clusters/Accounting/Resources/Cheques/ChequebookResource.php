<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages;
use Modules\Payment\Models\Chequebook;

class ChequebookResource extends Resource
{
    protected static ?string $model = Chequebook::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Cheque Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('bank_account_number')
                            ->required()
                            ->maxLength(255),
                        Select::make('bank_id') // Use relationship for dynamic fetching
                            ->label(__('accounting::cheque.bank'))
                             // Assuming we want to link to a currency or a journal.
                             // But Chequebook model has `bank_name` (string) not ID, based on previous migration.
                             // Re-checking migration: `bank_name` string.
                            ->visible(false), // Hiding for now until we clarify if we link to a Bank Account Journal

                        TextInput::make('bank_name')
                            ->required()
                            ->label(__('accounting::cheque.bank_name')),

                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ]),
                Section::make('Sequence')
                    ->schema([
                        TextInput::make('prefix')
                            ->label(__('accounting::cheque.prefix'))
                            ->maxLength(10),
                        TextInput::make('digits')
                            ->numeric()
                            ->default(6)
                            ->label(__('accounting::cheque.digits')),
                        TextInput::make('start_number')
                            ->numeric()
                            ->required()
                            ->default(1),
                        TextInput::make('next_number') // Helper, maybe read only or editable
                            ->numeric()
                            ->default(1),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('prefix')
                    ->searchable(),
                Tables\Columns\TextColumn::make('next_number')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChequebooks::route('/'),
            'create' => Pages\CreateChequebook::route('/create'),
            'edit' => Pages\EditChequebook::route('/{record}/edit'),
        ];
    }
}
