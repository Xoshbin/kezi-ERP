<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kezi\Purchase\Filament\Clusters\Purchases\PurchasesCluster;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\RelationManagers\VendorBillsRelationManager;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use Kezi\Purchase\Models\PurchaseOrder;

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
