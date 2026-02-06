<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AnalyticPlans\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends RelationManager<\Kezi\Accounting\Models\AnalyticPlan>
 */
class AnalyticAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'analyticAccounts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::analytic_account.analytic_accounts');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('accounting::analytic_plan.name'))
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('accounting::analytic_plan.name'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        \Filament\Forms\Components\Hidden::make('company_id')
                            ->default(Filament::getTenant()?->id),
                    ]),
            ])
            ->actions([
                DetachAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
