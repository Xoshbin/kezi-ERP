<?php

namespace Kezi\Foundation\Filament\Forms\Components;

use Filament\Actions\Action;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use Kezi\Foundation\Models\Partner;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class PartnerSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = Partner::class;
        $this->relationshipTitleAttribute = 'name';
        $this->configureForModel();

        $this->label(__('accounting::partner.label')); // Default label, can be overridden by users (e.g. Vendor, Customer)

        // Match PartnerResource search fields
        $this->searchableFields(['name', 'email', 'phone', 'contact_person', 'tax_id']);
        $this->searchable();
        $this->preload();

        $this->actionSchemaModel($this->modelClass);

        $this->createOptionForm(function (\Filament\Schemas\Schema $schema) {
            return PartnerResource::form($schema)->getComponents();
        });

        $this->createOptionModalHeading(__('accounting::partner.create'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('7xl');
        });

        $this->createOptionUsing(function (array $data): int {
            $partner = Partner::create($data);

            return $partner->getKey();
        });
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
