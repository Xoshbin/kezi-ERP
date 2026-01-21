<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Modules\Product\Models\Product;

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
                ->requiresConfirmation()
                ->visible(fn (Product $record) => $record->is_template)
                ->form([
                    \Filament\Forms\Components\Toggle::make('delete_existing')
                        ->label(__('product::product.delete_existing_variants'))
                        ->helperText(__('product::product.delete_existing_variants_help'))
                        ->default(false),
                ])
                ->action(function (Product $record, ?array $state = []) {
                    $action = app(\Modules\Product\Actions\GenerateProductVariantsAction::class);

                    $attributeValueMap = [];
                    $productAttributes = $state['product_attributes'] ?? $record->getAttribute('product_attributes') ?? [];

                    if (empty($productAttributes) && isset($this->data['product_attributes'])) {
                        $productAttributes = $this->data['product_attributes'];
                    }

                    foreach ($productAttributes as $attr) {
                        $attributeValueMap[$attr['product_attribute_id']] = $attr['values'];
                    }

                    $action->execute(new \Modules\Product\DataTransferObjects\GenerateProductVariantsDTO(
                        templateProductId: $record->id,
                        attributeValueMap: $attributeValueMap,
                        deleteExisting: $state['delete_existing'] ?? false,
                    ));

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Product variants generated successfully.')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
