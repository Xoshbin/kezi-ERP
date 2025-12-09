<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders;

use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\PurchasesCluster;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\RelationManagers\VendorBillsRelationManager;


class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = PurchasesCluster::class;

    protected static ?string $recordTitleAttribute = 'po_number';

    public static function getNavigationLabel(): string
    {
        return __('purchase::purchase_orders.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('purchase::purchase_orders.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase::purchase_orders.plural_label');
    }

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VendorBillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
