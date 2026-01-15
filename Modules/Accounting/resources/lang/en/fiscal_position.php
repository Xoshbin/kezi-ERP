<?php

return [
    // Labels
    'label' => 'Fiscal Position',
    'plural_label' => 'Fiscal Positions',

    // Basic Information
    'name' => 'Name',
    'country' => 'Country',
    'company' => 'Company',
    'auto_apply' => 'Auto Apply',
    'vat_required' => 'VAT Required',
    'zip_from' => 'Zip From',
    'zip_to' => 'Zip To',
    'criteria' => 'Criteria',
    'criteria_description' => 'Rules for automatic application of this fiscal position',

    // Timestamps
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',

    // Relation Managers
    'relation_managers' => [
        'account_mappings' => [
            'title' => 'Account Mappings',
            'original_account' => 'Original Account',
            'mapped_account' => 'Mapped Account',
        ],
        'tax_mappings' => [
            'title' => 'Tax Mappings',
            'original_tax' => 'Original Tax',
            'mapped_tax' => 'Mapped Tax',
        ],
    ],
];
