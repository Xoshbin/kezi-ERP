<?php

namespace App\Filament\Clusters\HumanResources\Resources\Positions;

use App\Filament\Clusters\HumanResources\HumanResourcesCluster;
use App\Filament\Clusters\HumanResources\Resources\Positions\Pages\CreatePosition;
use App\Filament\Clusters\HumanResources\Resources\Positions\Pages\EditPosition;
use App\Filament\Clusters\HumanResources\Resources\Positions\Pages\ListPositions;
use App\Filament\Clusters\HumanResources\Resources\Positions\Schemas\PositionForm;
use App\Filament\Clusters\HumanResources\Resources\Positions\Tables\PositionsTable;
use App\Models\Position;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class PositionResource extends Resource
{
    use Translatable;

    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('position.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('position.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('position.navigation_label');
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
