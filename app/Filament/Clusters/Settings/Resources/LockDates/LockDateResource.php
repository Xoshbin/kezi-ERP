<?php

namespace App\Filament\Clusters\Settings\Resources\LockDates;

use App\Enums\Accounting\LockDateType;
use App\Filament\Clusters\Settings\Resources\LockDates\Pages\CreateLockDate;
use App\Filament\Clusters\Settings\Resources\LockDates\Pages\EditLockDate;
use App\Filament\Clusters\Settings\Resources\LockDates\Pages\ListLockDates;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\LockDateResource\Pages;
use App\Models\LockDate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LockDateResource extends Resource
{
    protected static ?string $model = LockDate::class;

    protected static ?int $navigationSort = 7;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $cluster = SettingsCluster::class;


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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lock_type')
                    ->options(
                        collect(LockDateType::cases())
                            ->mapWithKeys(fn (LockDateType $type) => [$type->value => $type->label()])
                    )
                    ->required()
                    ->disabled(fn (?LockDate $record) => $record !== null && $record->lock_type === LockDateType::HardLock)
                    ->dehydrated(fn (?LockDate $record) => $record === null), // Only save on create
                DatePicker::make('locked_until')
                    ->required(),
                Select::make('company_id')
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
                TextColumn::make('lock_type')->badge(),
                TextColumn::make('locked_until')->date(),
                TextColumn::make('company.name')->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->disabled(fn (LockDate $record) => $record->lock_type === LockDateType::HardLock),
                DeleteAction::make()
                    ->disabled(fn (LockDate $record) => $record->lock_type === LockDateType::HardLock),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLockDates::route('/'),
            'create' => CreateLockDate::route('/create'),
            'edit' => EditLockDate::route('/{record}/edit'),
        ];
    }
}
