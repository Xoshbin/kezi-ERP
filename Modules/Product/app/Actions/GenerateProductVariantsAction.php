<?php

namespace Modules\Product\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Product\DataTransferObjects\GenerateProductVariantsDTO;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductAttributeValue;
use Modules\Product\Models\ProductVariantAttribute;

class GenerateProductVariantsAction
{
    /**
     * @return Collection<int, Product>
     */
    public function execute(GenerateProductVariantsDTO $dto): Collection
    {
        $template = Product::findOrFail($dto->templateProductId);

        if (! $template->is_template) {
            throw new \InvalidArgumentException('Product is not a template.');
        }

        return DB::transaction(function () use ($template, $dto) {
            $variants = collect();
            $combinations = $this->generateCombinations($dto->attributeValueMap);

            foreach ($combinations as $combination) {
                $variant = $this->createVariant($template, $combination);
                $variants->push($variant);
            }

            return $variants;
        });
    }

    /**
     * @param  array<int, array<int>>  $attributeValueMap
     * @return array<int, array<int, int>>
     */
    private function generateCombinations(array $attributeValueMap): array
    {
        $combinations = [[]];

        foreach ($attributeValueMap as $attributeId => $valueIds) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($valueIds as $valueId) {
                    $newCombinations[] = $combination + [$attributeId => $valueId];
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    /**
     * @param  array<int, int>  $combination  [attributeId => valueId]
     */
    private function createVariant(Product $template, array $combination): Product
    {
        // Get value names for SKU suffix
        $values = ProductAttributeValue::whereIn('id', array_values($combination))
            ->orderBy('product_attribute_id')
            ->get();

        $suffixParts = $values->map(fn ($v) => strtoupper($v->name));
        $suffix = $suffixParts->implode('-');

        $variant = $template->replicate();
        $variant->parent_product_id = $template->id;
        $variant->is_template = false;
        $variant->variant_sku_suffix = $suffix;
        $variant->sku = $template->sku.'-'.$suffix;

        // Ensure name includes variant info if desired, or keep as is.
        // Odoo usually keeps the same name but displays attributes separately.
        // We'll keep the name from template but add suffix to SKU.

        $variant->save();

        foreach ($combination as $attributeId => $valueId) {
            ProductVariantAttribute::create([
                'product_id' => $variant->id,
                'product_attribute_id' => $attributeId,
                'product_attribute_value_id' => $valueId,
            ]);
        }

        return $variant;
    }
}
