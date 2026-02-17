<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosProfiles;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
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

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $cluster = PosCluster::class;

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
