<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kezi\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages\CreateDeductionRule;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages\EditDeductionRule;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Pages\ListDeductionRules;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Schemas\DeductionRuleForm;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\DeductionRuleResource\Tables\DeductionRuleTable;
use Kezi\HR\Models\DeductionRule;

class DeductionRuleResource extends Resource
{
    protected static ?string $model = DeductionRule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-variable';

    protected static ?string $cluster = HumanResourcesCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('hr::payroll.deduction_rules');
    }

    public static function getModelLabel(): string
    {
        return __('hr::payroll.deduction_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::payroll.deduction_rules');
    }

    public static function form(Schema $schema): Schema
    {
        return DeductionRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeductionRuleTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeductionRules::route('/'),
            'create' => CreateDeductionRule::route('/create'),
            'edit' => EditDeductionRule::route('/{record}/edit'),
        ];
    }
}
