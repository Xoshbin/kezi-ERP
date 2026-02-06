<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages;
use Kezi\Manufacturing\Models\WorkCenter;

class WorkCenterResource extends Resource
{
    protected static ?string $model = WorkCenter::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('manufacturing::manufacturing.work_center.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('manufacturing::manufacturing.work_center.plural_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('manufacturing::navigation.groups.configuration');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('manufacturing::manufacturing.work_center.label'))
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label(__('manufacturing::manufacturing.work_center.code'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Forms\Components\TextInput::make('name')
                        ->label(__('manufacturing::manufacturing.work_center.name'))
                        ->required()
                        ->maxLength(255),

                    \Kezi\Foundation\Filament\Forms\Components\MoneyInput::make('hourly_cost')
                        ->label(__('manufacturing::manufacturing.work_center.hourly_cost'))
                        ->required()
                        ->minValue(0)
                        ->helperText(__('manufacturing::manufacturing.work_center.cost_helper')),

                    Forms\Components\TextInput::make('capacity')
                        ->label(__('manufacturing::manufacturing.work_center.capacity')) // Fixed: was Capacity (units/hour) hardcoded, now just Capacity, units suffix handled elsewhere or implied
                        ->numeric()
                        ->minValue(0)
                        ->helperText(__('manufacturing::manufacturing.work_center.capacity_helper')),

                    Forms\Components\Toggle::make('is_active')
                        ->label(__('manufacturing::manufacturing.work_center.is_active'))
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Textarea::make('notes')
                        ->label(__('manufacturing::manufacturing.work_center.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('manufacturing::manufacturing.work_center.code'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('manufacturing::manufacturing.work_center.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hourly_cost')
                    ->label(__('manufacturing::manufacturing.work_center.hourly_cost'))
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label(__('manufacturing::manufacturing.work_center.capacity'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' units/hr')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('manufacturing::manufacturing.work_center.is_active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('manufacturing::manufacturing.work_center.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('manufacturing::manufacturing.work_center.is_active'))
                    ->placeholder(__('manufacturing::manufacturing.placeholders.all'))
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkCenters::route('/'),
            'create' => Pages\CreateWorkCenter::route('/create'),
            'edit' => Pages\EditWorkCenter::route('/{record}/edit'),
        ];
    }
}
