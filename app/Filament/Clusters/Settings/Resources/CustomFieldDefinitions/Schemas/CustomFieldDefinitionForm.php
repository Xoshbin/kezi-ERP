<?php

namespace App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Schemas;

use App\Enums\CustomFields\CustomFieldType;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\CustomFieldDefinitionResource;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CustomFieldDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('custom_fields.sections.basic_information'))
                ->description(__('custom_fields.sections.basic_information_description'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('model_type')
                                ->label(__('custom_fields.fields.model_type'))
                                ->options(CustomFieldDefinitionResource::getAvailableModelTypes())
                                ->required()
                                ->searchable()
                                ->helperText(__('custom_fields.help.model_type'))
                                ->disabled(fn (?string $operation) => $operation === 'edit'),

                            Checkbox::make('is_active')
                                ->label(__('custom_fields.fields.is_active'))
                                ->default(true),
                        ]),

                    TextInput::make('name')
                        ->label(__('custom_fields.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label(__('custom_fields.fields.description'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(__('custom_fields.sections.field_definitions'))
                ->description(__('custom_fields.sections.field_definitions_description'))
                ->schema([
                    Repeater::make('field_definitions')
                        ->label(__('custom_fields.fields.field_definitions'))
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('key')
                                        ->label(__('custom_fields.fields.field_key'))
                                        ->required()
                                        ->maxLength(50)
                                        ->placeholder(__('custom_fields.placeholders.field_key'))
                                        ->helperText(__('custom_fields.help.field_key'))
                                        ->rules(['regex:/^[a-z0-9_]+$/'])
                                        ->validationMessages([
                                            'regex' => __('custom_fields.validation.field_key_format'),
                                        ]),

                                    TextInput::make('label')
                                        ->label(__('custom_fields.fields.field_label'))
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder(__('custom_fields.placeholders.field_label')),

                                    Select::make('type')
                                        ->label(__('custom_fields.fields.field_type'))
                                        ->options(
                                            collect(CustomFieldType::cases())
                                                ->mapWithKeys(fn (CustomFieldType $type) => [$type->value => $type->getLabel()])
                                        )
                                        ->required()
                                        ->helperText(__('custom_fields.help.field_type'))
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, ?string $state) {
                                            // Clear options if not select type
                                            if ($state !== CustomFieldType::Select->value) {
                                                $set('options', []);
                                            }
                                        }),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Checkbox::make('required')
                                        ->label(__('custom_fields.fields.field_required'))
                                        ->helperText(__('custom_fields.help.field_required')),

                                    TextInput::make('order')
                                        ->label(__('custom_fields.fields.field_order'))
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->helperText(__('custom_fields.help.field_order')),
                                ]),

                            Repeater::make('options')
                                ->label(__('custom_fields.fields.field_options'))
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('value')
                                                ->label(__('custom_fields.fields.option_value'))
                                                ->required()
                                                ->maxLength(100)
                                                ->placeholder(__('custom_fields.placeholders.option_value')),

                                            TextInput::make('label')
                                                ->label(__('custom_fields.fields.option_label'))
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder(__('custom_fields.placeholders.option_label')),
                                        ]),
                                ])
                                ->visible(fn (Get $get) => $get('type') === CustomFieldType::Select->value)
                                ->helperText(__('custom_fields.help.field_options'))
                                ->addActionLabel(__('custom_fields.actions.add_option'))
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    is_array($state['label'] ?? null)
                                        ? ($state['label'][app()->getLocale()] ?? $state['label']['en'] ?? null)
                                        : ($state['label'] ?? null)
                                ),

                            Textarea::make('help_text')
                                ->label(__('custom_fields.fields.field_help_text'))
                                ->maxLength(500)
                                ->rows(2)
                                ->placeholder(__('custom_fields.placeholders.field_help_text'))
                                ->columnSpanFull(),

                            TextInput::make('validation_rules')
                                ->label(__('custom_fields.fields.field_validation_rules'))
                                ->maxLength(500)
                                ->placeholder(__('custom_fields.placeholders.validation_rules'))
                                ->helperText(__('custom_fields.help.validation_rules'))
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel(__('custom_fields.actions.add_field'))
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            is_array($state['label'] ?? null)
                                ? ($state['label']['en'] ?? $state['label'][array_key_first($state['label'])] ?? null)
                                : ($state['label'] ?? $state['key'] ?? null)
                        )
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
