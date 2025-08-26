<?php

namespace App\Filament\Clusters\HumanResources\Resources\Payrolls;

use App\Filament\Clusters\HumanResources\HumanResourcesCluster;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Pages\ViewPayroll;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Schemas\PayrollForm;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Schemas\PayrollInfolist;
use App\Filament\Clusters\HumanResources\Resources\Payrolls\Tables\PayrollsTable;
use App\Models\Payroll;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'payroll_number';

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('payroll.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('payroll.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payroll.model_plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return PayrollForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PayrollInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'view' => ViewPayroll::route('/{record}'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }
}
