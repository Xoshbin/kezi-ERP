<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectInvoices;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Pages\CreateProjectInvoice;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Pages\EditProjectInvoice;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Pages\ListProjectInvoices;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Schemas\ProjectInvoiceForm;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Tables\ProjectInvoicesTable;
use Modules\ProjectManagement\Models\ProjectInvoice;

class ProjectInvoiceResource extends Resource
{
    protected static ?string $model = ProjectInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProjectInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectInvoicesTable::configure($table);
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
            'index' => ListProjectInvoices::route('/'),
            'create' => CreateProjectInvoice::route('/create'),
            'edit' => EditProjectInvoice::route('/{record}/edit'),
        ];
    }
}
