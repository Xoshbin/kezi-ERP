<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\RelationManagers;

use App\Enums\Inventory\ReorderingRoute;
use App\Models\ReorderingRule;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReorderingRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'reorderingRules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('reordering_rule.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('location_id')
                        ->relationship('location', 'name')
                        ->label(__('reordering_rule.fields.location'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('route')
                        ->label(__('reordering_rule.fields.route'))
                        ->options(
                            collect(ReorderingRoute::cases())
                                ->mapWithKeys(fn (ReorderingRoute $route) => [$route->value => $route->label()])
                        )
                        ->default(ReorderingRoute::MinMax->value)
                        ->required(),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('min_qty')
                        ->label(__('reordering_rule.fields.min_qty'))
                        ->helperText(__('reordering_rule.fields.min_qty_help'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.001)
                        ->required(),
                    TextInput::make('max_qty')
                        ->label(__('reordering_rule.fields.max_qty'))
                        ->helperText(__('reordering_rule.fields.max_qty_help'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.001)
                        ->required(),
                    TextInput::make('safety_stock')
                        ->label(__('reordering_rule.fields.safety_stock'))
                        ->helperText(__('reordering_rule.fields.safety_stock_help'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.001)
                        ->default(0),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('multiple')
                        ->label(__('reordering_rule.fields.multiple'))
                        ->helperText(__('reordering_rule.fields.multiple_help'))
                        ->numeric()
                        ->minValue(1)
                        ->step(0.001)
                        ->default(1),
                    TextInput::make('lead_time_days')
                        ->label(__('reordering_rule.fields.lead_time_days'))
                        ->helperText(__('reordering_rule.fields.lead_time_days_help'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Toggle::make('active')
                        ->label(__('reordering_rule.fields.active'))
                        ->default(true),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('location.name')
            ->columns([
                TextColumn::make('location.name')
                    ->label(__('reordering_rule.fields.location'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('route')
                    ->label(__('reordering_rule.fields.route'))
                    ->formatStateUsing(fn (ReorderingRoute $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('min_qty')
                    ->label(__('reordering_rule.fields.min_qty'))
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('max_qty')
                    ->label(__('reordering_rule.fields.max_qty'))
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('safety_stock')
                    ->label(__('reordering_rule.fields.safety_stock'))
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('lead_time_days')
                    ->label(__('reordering_rule.fields.lead_time_days'))
                    ->suffix(' days')
                    ->sortable(),
                IconColumn::make('active')
                    ->label(__('reordering_rule.fields.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('reordering_rule.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('location.name')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->mutateDataUsing(function (array $data): array {
                        /** @var \Modules\Product\Models\Product $owner */
                        $owner = $this->getOwnerRecord();
                        $data['company_id'] = $owner->getAttribute('company_id');
                        $data['product_id'] = $owner->getKey();

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ]);
    }
}
