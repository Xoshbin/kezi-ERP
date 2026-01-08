<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Modules\ProjectManagement\Enums\ProjectStatus;
use Modules\ProjectManagement\Models\Project;
use Modules\ProjectManagement\Services\ProjectService;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('manager.first_name')
                    ->label('Manager')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('budget_amount')
                    ->money(fn ($record) => $record->company->currency->code ?? 'USD')
                    ->sortable()
                    ->alignEnd(),
                IconColumn::make('is_billable')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('billing_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('activate')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Project $record) => $record->status === ProjectStatus::Draft || $record->status === ProjectStatus::OnHold)
                        ->action(fn (Project $record, ProjectService $service) => $service->activateProject($record)),
                    Action::make('complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Project $record) => $record->status === ProjectStatus::Active)
                        ->action(fn (Project $record, ProjectService $service) => $service->completeProject($record)),
                    Action::make('cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Project $record) => ! in_array($record->status, [ProjectStatus::Completed, ProjectStatus::Cancelled]))
                        ->action(fn (Project $record, ProjectService $service) => $service->cancelProject($record)),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
