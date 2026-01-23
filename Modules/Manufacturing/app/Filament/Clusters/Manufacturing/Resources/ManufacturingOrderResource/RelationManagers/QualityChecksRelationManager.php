<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource;

class QualityChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'qualityChecks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('qualitycontrol::check.navigation_label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('qualitycontrol::check.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('qualitycontrol::check.product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('qualitycontrol::check.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color())
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_blocking')
                    ->label(__('qualitycontrol::check.is_blocking'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inspectedByUser.name')
                    ->label(__('qualitycontrol::check.inspector'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('inspected_at')
                    ->label(__('qualitycontrol::check.inspected_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('qualitycontrol::check.status'))
                    ->options(collect(QualityCheckStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            ])
            ->headerActions([
                // Quality checks are usually auto-created or created via specific actions, not manually here
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => QualityCheckResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
