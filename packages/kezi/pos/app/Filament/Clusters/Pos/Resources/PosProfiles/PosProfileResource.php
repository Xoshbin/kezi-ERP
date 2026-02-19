<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Pages\CreatePosProfile;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Pages\EditPosProfile;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Pages\ListPosProfiles;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Schemas\PosProfileForm;
use Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles\Tables\PosProfilesTable;
use Kezi\Pos\Models\PosProfile;

class PosProfileResource extends Resource
{
    protected static ?string $model = PosProfile::class;

    protected static ?string $cluster = PosCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::Identification;

    public static function getModelLabel(): string
    {
        return __('pos::pos_profile.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pos::pos_profile.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pos::pos_profile.plural_label');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PosProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosProfilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosProfiles::route('/'),
            'create' => CreatePosProfile::route('/create'),
            'edit' => EditPosProfile::route('/{record}/edit'),
        ];
    }
}
