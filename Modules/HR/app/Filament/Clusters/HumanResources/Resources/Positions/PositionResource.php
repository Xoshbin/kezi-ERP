<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Positions;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages\CreatePosition;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages\EditPosition;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages\ListPositions;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Schemas\PositionForm;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Positions\Tables\PositionsTable;
use Modules\HR\Models\Position;

class PositionResource extends Resource
{
    use Translatable;

    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('hr::position.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('hr::position.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::position.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('hr::navigation.groups.hr_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return PositionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PositionsTable::configure($table);
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
            'index' => ListPositions::route('/'),
            'create' => CreatePosition::route('/create'),
            'edit' => EditPosition::route('/{record}/edit'),
        ];
    }
}
