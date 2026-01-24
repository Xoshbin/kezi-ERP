<?php

namespace Modules\Product\Filament\Clusters\Product\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Product\Filament\Clusters\Product\ProductCluster;
use Modules\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages\CreateProductCategory;
use Modules\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages\EditProductCategory;
use Modules\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages\ListProductCategories;
use Modules\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Schemas\ProductCategoryForm;
use Modules\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Tables\ProductCategoriesTable;
use Modules\Product\Models\ProductCategory;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static ?string $cluster = ProductCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProductCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductCategoriesTable::configure($table);
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
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
