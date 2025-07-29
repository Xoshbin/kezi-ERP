<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\JournalEntry;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Rules\ActiveAccount;
use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;
use App\Models\Account;
use App\Models\Partner;
use App\Models\AnalyticAccount as AnalyticAccountModel; // Use an alias to avoid conflict with the relationship name

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->getSearchResultsUsing(fn(string $search): array => \App\Models\Company::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn(string $value): ?string => \App\Models\Company::find($value)?->name)
                    ->required(),
                Forms\Components\Select::make('journal_id')
                    ->relationship('journal', 'name')
                    ->required(),
                Forms\Components\Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                Forms\Components\TextInput::make('exchange_rate')
                    ->required()
                    ->numeric()
                    ->default(1.000000),
                Forms\Components\Select::make('created_by_user_id')
                    ->relationship('createdBy', 'name')
                    ->required(),
                Forms\Components\DatePicker::make('entry_date')
                    ->required(),
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->schema([
                        Forms\Components\Select::make('account_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Account::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Account::find($value)?->name)
                            ->rules([new ActiveAccount])
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('debit')->required()->numeric()->columnSpan(1),
                        Forms\Components\TextInput::make('credit')->required()->numeric()->columnSpan(1),
                        Forms\Components\Select::make('partner_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Partner::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => Partner::find($value)?->name)
                            ->columnSpan(2),
                        Forms\Components\Select::make('analytic_account_id')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => AnalyticAccountModel::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => AnalyticAccountModel::find($value)?->name)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('description')->maxLength(255)->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_posted')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('hash')
                    ->maxLength(64),
                Forms\Components\TextInput::make('previous_hash')
                    ->maxLength(64),
                Forms\Components\TextInput::make('source_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('source_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_posted')
                    ->boolean(),
                Tables\Columns\TextColumn::make('currency_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('exchange_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_debit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_credit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hash')
                    ->searchable(),
                Tables\Columns\TextColumn::make('previous_hash')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\JournalEntryLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
