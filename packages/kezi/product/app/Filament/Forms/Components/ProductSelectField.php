<?php

namespace Kezi\Product\Filament\Forms\Components;

use Filament\Actions\Action;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Kezi\Product\Models\Product;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class ProductSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->model(Product::class);
        $this->label(__('product::product.product'));
        $this->searchableFields(['name', 'sku', 'description']);
        $this->searchable();
        $this->preload();

        $this->createOptionForm(function (\Filament\Schemas\Schema $schema) {
            return ProductResource::form($schema)->getComponents();
        });

        $this->createOptionModalHeading(__('product::product.create'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('7xl');
        });

        $this->createOptionUsing(function (array $data): int {
            $product = Product::create($data);

            return $product->getKey();
        });
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
