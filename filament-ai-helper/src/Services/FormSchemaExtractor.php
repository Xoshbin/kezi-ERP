<?php

namespace AccounTech\FilamentAiHelper\Services;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Form;
use Illuminate\Support\Str;

class FormSchemaExtractor
{
    /**
     * Extract form schema from a Filament form
     */
    public function extractFromForm(Form $form): array
    {
        $schema = [];
        $components = $form->getComponents();
        
        foreach ($components as $component) {
            $this->extractComponentSchema($component, $schema);
        }
        
        return $schema;
    }

    /**
     * Extract schema from form components recursively
     */
    private function extractComponentSchema(Component $component, array &$schema, string $prefix = ''): void
    {
        $name = $component->getName();
        
        if (!$name) {
            // Handle container components (like sections, fieldsets)
            if (method_exists($component, 'getChildComponents')) {
                foreach ($component->getChildComponents() as $child) {
                    $this->extractComponentSchema($child, $schema, $prefix);
                }
            }
            return;
        }
        
        $fullName = $prefix ? "{$prefix}.{$name}" : $name;
        
        $schema[$fullName] = [
            'type' => $this->getComponentType($component),
            'label' => $component->getLabel(),
            'required' => $component->isRequired(),
            'validation' => $this->extractValidationRules($component),
            'options' => $this->extractOptions($component),
            'placeholder' => $this->getPlaceholder($component),
            'help' => $component->getHelperText(),
        ];
        
        // Handle nested components (like repeaters, fieldsets)
        if (method_exists($component, 'getChildComponents')) {
            foreach ($component->getChildComponents() as $child) {
                $this->extractComponentSchema($child, $schema, $fullName);
            }
        }
    }

    /**
     * Get the component type
     */
    private function getComponentType(Component $component): string
    {
        return match (true) {
            $component instanceof TextInput => 'text',
            $component instanceof Textarea => 'textarea',
            $component instanceof Select => 'select',
            $component instanceof DatePicker => 'date',
            $component instanceof Toggle => 'boolean',
            $component instanceof CheckboxList => 'checkbox_list',
            $component instanceof Radio => 'radio',
            default => 'text'
        };
    }

    /**
     * Extract validation rules from component
     */
    private function extractValidationRules(Component $component): array
    {
        $rules = [];
        
        if ($component->isRequired()) {
            $rules[] = 'required';
        }
        
        // Extract specific validation rules based on component type
        if ($component instanceof TextInput) {
            if ($component->getMaxLength()) {
                $rules[] = "max:{$component->getMaxLength()}";
            }
            if ($component->getMinLength()) {
                $rules[] = "min:{$component->getMinLength()}";
            }
        }
        
        return $rules;
    }

    /**
     * Extract options for select/radio/checkbox components
     */
    private function extractOptions(Component $component): array
    {
        if (!($component instanceof Select) && 
            !($component instanceof CheckboxList) && 
            !($component instanceof Radio)) {
            return [];
        }
        
        try {
            $options = $component->getOptions();
            return is_array($options) ? $options : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get placeholder text
     */
    private function getPlaceholder(Component $component): ?string
    {
        if (method_exists($component, 'getPlaceholder')) {
            return $component->getPlaceholder();
        }
        
        return null;
    }

    /**
     * Extract current form data from Livewire component
     */
    public function extractCurrentFormData($livewireComponent): array
    {
        if (!$livewireComponent) {
            return [];
        }
        
        try {
            // Try to get form data from Livewire component
            if (method_exists($livewireComponent, 'getFormData')) {
                return $livewireComponent->getFormData();
            }
            
            // Try to get data property
            if (property_exists($livewireComponent, 'data')) {
                return $livewireComponent->data ?? [];
            }
            
            // Try to get form state
            if (method_exists($livewireComponent, 'getForm')) {
                $form = $livewireComponent->getForm();
                if ($form && method_exists($form, 'getState')) {
                    return $form->getState();
                }
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Detect page type from URL or component
     */
    public function detectPageType($request = null): ?string
    {
        if (!$request) {
            $request = request();
        }
        
        $url = $request->url();
        
        if (Str::contains($url, '/create')) {
            return 'create';
        }
        
        if (Str::contains($url, '/edit')) {
            return 'edit';
        }
        
        return null;
    }

    /**
     * Extract form schema from Filament resource page
     */
    public function extractFromResourcePage($page): array
    {
        if (!$page) {
            return [];
        }
        
        try {
            // Try to get form from the page
            if (method_exists($page, 'getForm')) {
                $form = $page->getForm();
                if ($form) {
                    return $this->extractFromForm($form);
                }
            }
            
            // Try to get form schema directly
            if (method_exists($page, 'getFormSchema')) {
                $schema = $page->getFormSchema();
                if ($schema) {
                    $extractedSchema = [];
                    foreach ($schema as $component) {
                        $this->extractComponentSchema($component, $extractedSchema);
                    }
                    return $extractedSchema;
                }
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validate form data against schema
     */
    public function validateFormData(array $data, array $schema): array
    {
        $errors = [];
        
        foreach ($schema as $field => $config) {
            $value = $data[$field] ?? null;
            
            // Check required fields
            if ($config['required'] && (is_null($value) || $value === '')) {
                $errors[$field] = "Field {$field} is required";
                continue;
            }
            
            // Type validation
            if (!is_null($value)) {
                $errors = array_merge($errors, $this->validateFieldType($field, $value, $config));
            }
        }
        
        return $errors;
    }

    /**
     * Validate field type
     */
    private function validateFieldType(string $field, $value, array $config): array
    {
        $errors = [];
        $type = $config['type'];
        
        switch ($type) {
            case 'date':
                if (!$this->isValidDate($value)) {
                    $errors[$field] = "Field {$field} must be a valid date";
                }
                break;
                
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1', 'true', 'false'])) {
                    $errors[$field] = "Field {$field} must be a boolean value";
                }
                break;
                
            case 'select':
                if (!empty($config['options']) && !array_key_exists($value, $config['options'])) {
                    $errors[$field] = "Field {$field} must be one of the available options";
                }
                break;
        }
        
        return $errors;
    }

    /**
     * Check if value is a valid date
     */
    private function isValidDate($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
}
