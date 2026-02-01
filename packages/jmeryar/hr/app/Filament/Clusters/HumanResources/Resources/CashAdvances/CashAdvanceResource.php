<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Jmeryar\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\CreateCashAdvance;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\EditCashAdvance;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\ListCashAdvances;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Pages\ViewCashAdvance;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Schemas\CashAdvanceForm;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Schemas\CashAdvanceInfolist;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Tables\CashAdvancesTable;
use Jmeryar\HR\Models\CashAdvance;

class CashAdvanceResource extends Resource
{
    protected static ?string $model = CashAdvance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'advance_number';

    public static function getModelLabel(): string
    {
        return __('hr::cash_advance.navigation.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::cash_advance.navigation.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr::cash_advance.navigation.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return CashAdvanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashAdvanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashAdvancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ExpenseReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashAdvances::route('/'),
            'create' => CreateCashAdvance::route('/create'),
            'view' => ViewCashAdvance::route('/{record}'),
            'edit' => EditCashAdvance::route('/{record}/edit'),
        ];
    }
}
