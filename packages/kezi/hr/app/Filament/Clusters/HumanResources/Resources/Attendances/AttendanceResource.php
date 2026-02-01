<?php

declare(strict_types=1);

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kezi\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\Schemas\AttendanceForm;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\Tables\AttendancesTable;
use Kezi\HR\Models\Attendance;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'attendance_date';

    public static function getNavigationLabel(): string
    {
        return __('hr::attendance.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
