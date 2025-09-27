<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders;

use App\Filament\Clusters\Purchases\PurchasesCluster;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\RelationManagers\VendorBillsRelationManager;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = PurchasesCluster::class;

    protected static ?string $recordTitleAttribute = 'po_number';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?string $modelLabel = 'Purchase Order';

    protected static ?string $pluralModelLabel = 'Purchase Orders';

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
