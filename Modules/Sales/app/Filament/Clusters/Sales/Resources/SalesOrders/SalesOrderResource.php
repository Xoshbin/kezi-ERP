<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ListSalesOrders;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages\ViewSalesOrder;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\RelationManagers\InvoicesRelationManager;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Schemas\SalesOrderForm;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Tables\SalesOrdersTable;
use Modules\Sales\Filament\Clusters\Sales\SalesCluster;
use Modules\Sales\Models\SalesOrder;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $recordTitleAttribute = 'so_number';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return SalesOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesOrders::route('/'),
            'create' => CreateSalesOrder::route('/create'),
            'view' => ViewSalesOrder::route('/{record}'),
            'edit' => EditSalesOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::sales_orders.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('sales::sales_orders.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sales::sales_orders.model.plural_label');
    }
}
