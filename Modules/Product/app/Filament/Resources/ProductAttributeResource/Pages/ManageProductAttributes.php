<?php

namespace Modules\Product\Filament\Resources\ProductAttributeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Modules\Product\Filament\Resources\ProductAttributeResource;

class ManageProductAttributes extends ManageRecords
{
    protected static string $resource = ProductAttributeResource::class;

    public function getTitle(): string
    {
        return __('product::product.attributes');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('product-attribute'),
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    /** @var \App\Models\Company|null $tenant */
                    $tenant = \Filament\Facades\Filament::getTenant();
                    $data['company_id'] = $tenant?->id;

                    return $data;
                }),
        ];
    }
}
