<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\LockDate;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Clusters\Settings;
use App\Enums\Accounting\LockDateType;
use App\Filament\Resources\LockDateResource\Pages;

class LockDateResource extends Resource
{
    protected static ?string $model = LockDate::class;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;


    public static function getModelLabel(): string
    {
        return __('lock_date.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lock_date.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('lock_date.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lock_type')
                    ->options(
                        collect(LockDateType::cases())
                            ->mapWithKeys(fn (LockDateType $type) => [$type->value => $type->label()])
                    )
                    ->required()
                    ->disabled(fn (?LockDate $record) => $record !== null && $record->lock_type === LockDateType::HardLock)
                    ->dehydrated(fn (?LockDate $record) => $record === null), // Only save on create
                Forms\Components\DatePicker::make('locked_until')
                    ->required(),
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required()
                    ->default(fn () => auth()->user()->company_id)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lock_type')->badge(),
                Tables\Columns\TextColumn::make('locked_until')->date(),
                Tables\Columns\TextColumn::make('company.name')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->disabled(fn (LockDate $record) => $record->lock_type === LockDateType::HardLock),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (LockDate $record) => $record->lock_type === LockDateType::HardLock),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLockDates::route('/'),
            'create' => Pages\CreateLockDate::route('/create'),
            'edit' => Pages\EditLockDate::route('/{record}/edit'),
        ];
    }
}
