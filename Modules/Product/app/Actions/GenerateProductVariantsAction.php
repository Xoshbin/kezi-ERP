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
     * @param  array<int, array<int, int>>|null  $filteredCombinations
     * @return Collection<int, Product>
     */
    public function execute(GenerateProductVariantsDTO $dto, ?array $filteredCombinations = null): Collection
    {
        $template = Product::findOrFail($dto->templateProductId);

        if (! $template->is_template) {
            throw new \InvalidArgumentException('Product is not a template.');
        }

        return DB::transaction(function () use ($template, $dto, $filteredCombinations) {
            if ($dto->deleteExisting && $template->variants()->exists()) {
                $this->validateVariantsDeletable($template);

                foreach ($template->variants as $variant) {
                    $variant->forceDelete();
                }
            }

            $variants = collect();
            $combinations = $filteredCombinations ?? $this->generateCombinations($dto->attributeValueMap);

            foreach ($combinations as $combination) {
                $details = $this->getVariantDetails($template, $combination);

                if (Product::where('sku', $details['sku'])->exists()) {
                    continue;
                }

                $variant = $this->createVariant($template, $combination, $details);
                $variants->push($variant);
            }

            return $variants;
        });
    }

    /**
     * @param  array<int, array<int>>  $attributeValueMap
     * @return array<int, array{sku: string, values: string, combination: array<int, int>}>
     */
    public function previewCombinations(int $templateId, array $attributeValueMap): array
    {
        $template = Product::findOrFail($templateId);
        $combinations = $this->generateCombinations($attributeValueMap);
        $preview = [];

        foreach ($combinations as $combination) {
            $details = $this->getVariantDetails($template, $combination);
            $values = ProductAttributeValue::whereIn('id', array_values($combination))
                ->get()
                ->map(fn ($v) => $v->name)
                ->implode(', ');

            $preview[] = [
                'sku' => $details['sku'],
                'values' => $values,
                'combination' => $combination,
            ];
        }

        return $preview;
    }

    private function validateVariantsDeletable(Product $template): void
    {
        foreach ($template->variants as $variant) {
            if ($variant->stockMoveProductLines()->exists() ||
                $variant->invoiceLines()->exists() ||
                $variant->vendorBillLines()->exists()) {
                throw new \RuntimeException(
                    "Cannot regenerate variants for '{$template->sku}'. ".
                    "Variant '{$variant->sku}' has existing transactions (Stock Moves, Invoices, or Bills)."
                );
            }
        }
    }

    /**
     * @param  array<int, int>  $combination
     * @return array{suffix: string, sku: string}
     */
    private function getVariantDetails(Product $template, array $combination): array
    {
        $values = ProductAttributeValue::whereIn('id', array_values($combination))
            ->orderBy('product_attribute_id')
            ->get();

        $suffixParts = $values->map(fn ($v) => strtoupper($v->name));
        $suffix = $suffixParts->implode('-');

        return [
            'suffix' => $suffix,
            'sku' => $template->sku.'-'.$suffix,
        ];
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
     * @param  array{suffix: string, sku: string}  $details
     */
    private function createVariant(Product $template, array $combination, array $details): Product
    {
        $variant = $template->replicate();
        $variant->parent_product_id = $template->id;
        $variant->is_template = false;
        $variant->variant_sku_suffix = $details['suffix'];
        $variant->sku = $details['sku'];

        // Explicitly copy tracking_type from template to ensure it is inherited
        $variant->tracking_type = $template->tracking_type;

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
