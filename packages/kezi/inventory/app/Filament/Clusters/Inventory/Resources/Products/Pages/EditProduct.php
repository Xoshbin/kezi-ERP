<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Kezi\Product\Models\Product;

class EditProduct extends EditRecord
{
    use Translatable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            \Filament\Actions\Action::make('generate_variants')
                ->label(__('product::product.actions.generate_variants'))
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->visible(fn (Product $record) => $record->is_template)
                ->form([
                    \Filament\Schemas\Components\Wizard::make([
                        \Filament\Schemas\Components\Wizard\Step::make(__('product::product.variant_generation.options'))
                            ->schema([
                                \Filament\Forms\Components\Toggle::make('delete_existing')
                                    ->label(__('product::product.delete_existing_variants'))
                                    ->helperText(__('product::product.delete_existing_variants_help'))
                                    ->default(false),
                            ]),
                        \Filament\Schemas\Components\Wizard\Step::make(__('product::product.variant_generation.preview'))
                            ->schema([
                                \Filament\Forms\Components\CheckboxList::make('selected_variants')
                                    ->label(__('product::product.variant_generation.select_variants'))
                                    ->options(function (Product $record, $livewire) {
                                        $action = app(\Kezi\Product\Actions\GenerateProductVariantsAction::class);
                                        $productAttributes = (isset($livewire->data['product_attributes']) && ! empty($livewire->data['product_attributes']))
                                            ? $livewire->data['product_attributes']
                                            : ($record->product_attributes ?? []);

                                        $attributeValueMap = [];
                                        foreach ($productAttributes as $attr) {
                                            $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                                        }
                                        $previews = $action->previewCombinations($record->id, $attributeValueMap);

                                        return collect($previews)->mapWithKeys(fn ($p, $i) => [(string) $i => "{$p['sku']} ({$p['values']})"]);
                                    })
                                    ->default(function (Product $record, $livewire) {
                                        $action = app(\Kezi\Product\Actions\GenerateProductVariantsAction::class);
                                        $productAttributes = (isset($livewire->data['product_attributes']) && ! empty($livewire->data['product_attributes']))
                                            ? $livewire->data['product_attributes']
                                            : ($record->product_attributes ?? []);

                                        $attributeValueMap = [];
                                        foreach ($productAttributes as $attr) {
                                            $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                                        }
                                        $previews = $action->previewCombinations($record->id, $attributeValueMap);

                                        return array_map('strval', array_keys($previews));
                                    })
                                    ->columns(2)
                                    ->required(),
                            ]),
                    ]),
                ])
                ->action(function (\Filament\Actions\Action $action, Product $record, $livewire) {
                    $state = $action->getFormData();
                    $actionLogic = app(\Kezi\Product\Actions\GenerateProductVariantsAction::class);

                    $attributeValueMap = [];
                    $productAttributes = (isset($livewire->data['product_attributes']) && ! empty($livewire->data['product_attributes']))
                        ? $livewire->data['product_attributes']
                        : ($record->product_attributes ?? []);

                    foreach ($productAttributes as $attr) {
                        $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                    }

                    $allCombinations = $actionLogic->previewCombinations($record->id, $attributeValueMap);
                    $selectedIndices = $state['selected_variants'] ?? [];
                    $filteredCombinations = [];

                    foreach ($selectedIndices as $index) {
                        if (isset($allCombinations[(int) $index])) {
                            $filteredCombinations[] = $allCombinations[(int) $index]['combination'];
                        }
                    }

                    $actionLogic->execute(new \Kezi\Product\DataTransferObjects\GenerateProductVariantsDTO(
                        templateProductId: $record->id,
                        attributeValueMap: $attributeValueMap,
                        deleteExisting: $state['delete_existing'] ?? false,
                    ), $filteredCombinations);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title(__('product::product.actions.generate_variants_success'))
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
