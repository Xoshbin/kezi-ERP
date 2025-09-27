<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\HR\Models\Payroll;

class PayrollResource extends Resource
{
    use Translatable;

    protected static ?string $model = Payroll::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

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
