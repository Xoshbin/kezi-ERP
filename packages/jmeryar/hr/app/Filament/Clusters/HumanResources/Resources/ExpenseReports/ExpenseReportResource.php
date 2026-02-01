<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Jmeryar\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\CreateExpenseReport;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\EditExpenseReport;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\ListExpenseReports;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Pages\ViewExpenseReport;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas\ExpenseReportForm;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Schemas\ExpenseReportInfolist;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\ExpenseReports\Tables\ExpenseReportsTable;
use Jmeryar\HR\Models\ExpenseReport;

class ExpenseReportResource extends Resource
{
    protected static ?string $model = ExpenseReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'report_number';

    public static function getModelLabel(): string
    {
        return __('hr::expense_report.navigation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::expense_report.navigation.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExpenseReportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseReportsTable::configure($table);
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
            'index' => ListExpenseReports::route('/'),
            'create' => CreateExpenseReport::route('/create'),
            'view' => ViewExpenseReport::route('/{record}'),
            'edit' => EditExpenseReport::route('/{record}/edit'),
        ];
    }
}
