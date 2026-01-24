<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Manufacturing\Enums\WorkOrderStatus;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('manufacturing::manufacturing.work_order.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label(__('manufacturing::manufacturing.work_order.operation'))
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('work_center_id')
                ->label(__('manufacturing::manufacturing.work_order.work_center'))
                ->relationship('workCenter', 'name')
                ->required(),

            Forms\Components\TextInput::make('planned_duration')
                ->label(__('manufacturing::manufacturing.work_order.planned_duration'))
                ->numeric()
                ->required(),

            Forms\Components\DateTimePicker::make('planned_start_at')
                ->label(__('manufacturing::manufacturing.work_order.planned_start_at'))
                ->disabled(),

            Forms\Components\DateTimePicker::make('planned_finished_at')
                ->label(__('manufacturing::manufacturing.work_order.planned_finished_at'))
                ->disabled(),

            Forms\Components\Select::make('status')
                ->label(__('manufacturing::manufacturing.work_order.status'))
                ->options(WorkOrderStatus::class)
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('sequence')
                    ->label(__('manufacturing::manufacturing.work_order.sequence'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('manufacturing::manufacturing.work_order.operation'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('workCenter.name')
                    ->label(__('manufacturing::manufacturing.work_order.work_center'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_duration')
                    ->label(__('manufacturing::manufacturing.work_order.planned_duration'))
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\TextColumn::make('planned_start_at')
                    ->label(__('manufacturing::manufacturing.work_order.planned_start_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('planned_finished_at')
                    ->label(__('manufacturing::manufacturing.work_order.planned_finished_at'))
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('manufacturing::manufacturing.work_order.status'))
                    ->colors([
                        'secondary' => WorkOrderStatus::Pending->value,
                        'warning' => WorkOrderStatus::Ready->value,
                        'primary' => WorkOrderStatus::InProgress->value,
                        'success' => WorkOrderStatus::Done->value,
                        'danger' => WorkOrderStatus::Cancelled->value,
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
