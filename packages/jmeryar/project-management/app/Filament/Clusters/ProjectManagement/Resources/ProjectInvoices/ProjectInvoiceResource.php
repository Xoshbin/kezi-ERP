<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\ProjectManagementCluster;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages\CreateProjectInvoice;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages\EditProjectInvoice;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages\ListProjectInvoices;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Schemas\ProjectInvoiceForm;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Tables\ProjectInvoicesTable;
use Jmeryar\ProjectManagement\Models\ProjectInvoice;

class ProjectInvoiceResource extends Resource
{
    protected static ?string $cluster = ProjectManagementCluster::class;

    public static function getModelLabel(): string
    {
        return __('projectmanagement::project.invoice.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projectmanagement::project.invoice.plural_label');
    }

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
