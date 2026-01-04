<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Purchase\Filament\Clusters\Purchases\PurchasesCluster;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\EditRequestForQuotation;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\ListRequestForQuotations;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\ViewRequestForQuotation;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Schemas\RequestForQuotationForm;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Tables\RequestForQuotationsTable;
use Modules\Purchase\Models\RequestForQuotation;

class RequestForQuotationResource extends Resource
{
    protected static ?string $model = RequestForQuotation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $cluster = PurchasesCluster::class;

    protected static ?string $recordTitleAttribute = 'rfq_number';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return RequestForQuotationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestForQuotationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequestForQuotations::route('/'),
            'create' => CreateRequestForQuotation::route('/create'),
            'view' => ViewRequestForQuotation::route('/{record}'),
            'edit' => EditRequestForQuotation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('purchase::request_for_quotation.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('purchase::request_for_quotation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase::request_for_quotation.plural_label');
    }
}
