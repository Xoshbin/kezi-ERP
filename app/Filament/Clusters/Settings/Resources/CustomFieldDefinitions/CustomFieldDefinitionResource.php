<?php

namespace App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions;

use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\CreateCustomFieldDefinition;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\EditCustomFieldDefinition;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\ListCustomFieldDefinitions;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Schemas\CustomFieldDefinitionForm;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Tables\CustomFieldDefinitionsTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\CustomFieldDefinition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class CustomFieldDefinitionResource extends Resource
{
    use Translatable;

    protected static ?string $model = CustomFieldDefinition::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'model_type'];
    }

    public static function getNavigationLabel(): string
    {
        return __('custom_fields.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('custom_fields.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('custom_fields.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return CustomFieldDefinitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomFieldDefinitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFieldDefinitions::route('/'),
            'create' => CreateCustomFieldDefinition::route('/create'),
            'edit' => EditCustomFieldDefinition::route('/{record}/edit'),
        ];
    }

    /**
     * Get available model types for custom fields.
     *
     * @return array<string, string>
     */
    public static function getAvailableModelTypes(): array
    {
        return [
            'App\\Models\\Partner' => __('custom_fields.model_types.App\\Models\\Partner'),
            'App\\Models\\Product' => __('custom_fields.model_types.App\\Models\\Product'),
            'App\\Models\\Employee' => __('custom_fields.model_types.App\\Models\\Employee'),
            'App\\Models\\Department' => __('custom_fields.model_types.App\\Models\\Department'),
            'App\\Models\\Position' => __('custom_fields.model_types.App\\Models\\Position'),
            'App\\Models\\Asset' => __('custom_fields.model_types.App\\Models\\Asset'),
        ];
    }
}
