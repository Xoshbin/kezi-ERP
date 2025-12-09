<?php

namespace Modules\Accounting\Filament\Resources\LockDates;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Filament\Resources\LockDates\Pages\CreateLockDate;
use Modules\Accounting\Filament\Resources\LockDates\Pages\EditLockDate;
use Modules\Accounting\Filament\Resources\LockDates\Pages\ListLockDates;
use Modules\Accounting\Models\LockDate;

class LockDateResource extends Resource
{
    protected static ?string $model = LockDate::class;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $cluster = SettingsCluster::class;

    public static function getModelLabel(): string
    {
        return __('accounting::lock_date.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::lock_date.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::lock_date.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::lock_date.basic_information'))
                    ->schema([
                        Select::make('lock_type')
                            ->label(__('accounting::lock_date.lock_type'))
                            ->options(
                                collect(LockDateType::cases())
                                    ->mapWithKeys(fn (LockDateType $type) => [$type->value => $type->label()])
                            )
                            ->required()
                            ->disabled(fn (?LockDate $record) => $record !== null && $record->lock_type === LockDateType::HardLock)
                            ->dehydrated(fn (?LockDate $record) => $record === null),
                        DatePicker::make('locked_until')
                            ->label(__('accounting::lock_date.locked_until'))
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lock_type')
                    ->badge()
                    ->label(__('accounting::lock_date.lock_type')),
                TextColumn::make('locked_until')
                    ->date()
                    ->label(__('accounting::lock_date.locked_until')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('accounting::lock_date.created_at')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('accounting::lock_date.updated_at')),
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
