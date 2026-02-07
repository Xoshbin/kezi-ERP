<?php

return [
    'label' => 'Custom Field Definition',
    'plural_label' => 'Custom Field Definitions',
    'navigation_label' => 'Custom Fields',
    'section_title' => 'Custom Fields',

    'fields' => [
        'model_type' => 'Model Type',
        'name' => 'Name',
        'description' => 'Description',
        'is_active' => 'Active',
        'field_definitions' => 'Field Definitions',
        'field_key' => 'Field Key',
        'field_label' => 'Field Label',
        'field_type' => 'Field Type',
        'field_required' => 'Required',
        'show_in_table' => 'Show in Table',
        'field_options' => 'Options',
        'field_validation_rules' => 'Validation Rules',
        'field_help_text' => 'Help Text',
        'option_value' => 'Value',
        'option_label' => 'Label',
    ],

    'sections' => [
        'basic_information' => 'Basic Information',
        'basic_information_description' => 'Configure the basic settings for this custom field definition.',
        'field_definitions' => 'Field Definitions',
        'field_definitions_description' => 'Define the custom fields that will be available for this model.',
        'field_configuration' => 'Field Configuration',
        'field_options' => 'Field Options',
        'field_validation' => 'Validation & Help',
    ],

    'actions' => [
        'add_field' => 'Add Field',
        'remove_field' => 'Remove Field',
        'add_option' => 'Add Option',
        'remove_option' => 'Remove Option',
        'move_up' => 'Move Up',
        'move_down' => 'Move Down',
    ],

    'placeholders' => [
        'field_key' => 'e.g., emergency_contact',
        'field_label' => 'e.g., Emergency Contact',
        'field_help_text' => 'Additional information to help users fill this field',
        'validation_rules' => 'e.g., max:255, email',
        'option_value' => 'e.g., option1',
        'option_label' => 'e.g., Option 1',
    ],

    'help' => [
        'model_type' => 'Select the model type that will use these custom fields.',
        'field_key' => 'A unique identifier for this field. Use lowercase letters, numbers, and underscores only.',
        'field_type' => 'The type of input field that will be displayed to users.',
        'field_required' => 'Whether this field must be filled by users.',
        'show_in_table' => 'Whether this field should be displayed as a column in resource table lists.',
        'field_options' => 'For select fields, define the available options.',
        'validation_rules' => 'Additional Laravel validation rules (comma-separated).',
    ],

    'validation' => [
        'field_key_required' => 'Field key is required.',
        'field_key_unique' => 'Field key must be unique within this definition.',
        'field_key_format' => 'Field key must contain only lowercase letters, numbers, and underscores.',
        'field_label_required' => 'Field label is required.',
        'field_type_required' => 'Field type is required.',
        'select_options_required' => 'Select fields must have at least one option.',
        'option_value_required' => 'Option value is required.',
        'option_label_required' => 'Option label is required.',
    ],

    'messages' => [
        'no_fields_defined' => 'No custom fields have been defined for this model yet.',
        'definition_saved' => 'Custom field definition saved successfully.',
        'definition_deleted' => 'Custom field definition deleted successfully.',
        'field_added' => 'Field added successfully.',
        'field_removed' => 'Field removed successfully.',
        'invalid_model_type' => 'Invalid model type selected.',
    ],

    'model_types' => [
        'App\\Models\\Partner' => 'Partners',
        'App\\Models\\Product' => 'Products',
        'App\\Models\\Employee' => 'Employees',
        'App\\Models\\Department' => 'Departments',
        'App\\Models\\Position' => 'Positions',
        'App\\Models\\Asset' => 'Assets',
        'App\\Models\\Project' => 'Projects',
    ],
];
