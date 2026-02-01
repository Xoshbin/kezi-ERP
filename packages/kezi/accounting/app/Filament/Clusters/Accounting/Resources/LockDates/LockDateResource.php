<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates;

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
use Kezi\Accounting\Enums\Accounting\LockDateType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages\CreateLockDate;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages\EditLockDate;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages\ListLockDates;
use Kezi\Accounting\Models\LockDate;

class LockDateResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $model = LockDate::class;

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

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

    public static function getNavigationGroup(): string
    {
        return __('accounting::navigation.groups.administration');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::lock_date.basic_information'))
                    ->schema([
                        \Filament\Forms\Components\Hidden::make('company_id')
                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}
