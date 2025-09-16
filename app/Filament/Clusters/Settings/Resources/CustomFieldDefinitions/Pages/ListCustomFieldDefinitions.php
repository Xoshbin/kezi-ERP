<?php

namespace App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages;

use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\CustomFieldDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class ListCustomFieldDefinitions extends ListRecords
{
    use Translatable;

    protected static string $resource = CustomFieldDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Create Custom Field Definition')),
        ];
    }

    public function getTitle(): string
    {
        return __('custom_fields.plural_label');
    }
}
