<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Kezi\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages\EditLeaveRequest;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages\ListLeaveRequests;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use Kezi\HR\Models\LeaveRequest;

class LeaveRequestResource extends Resource
{
    // use Translatable;

    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'request_number';

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['request_number', 'employee.first_name', 'employee.last_name', 'reason'];
    }

    public static function getNavigationLabel(): string
    {
        return __('hr::leave_request.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('hr::leave_request.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::leave_request.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
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
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
