<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\CreateQuote;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\EditQuote;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\ListQuotes;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages\ViewQuote;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Schemas\QuoteForm;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Tables\QuotesTable;
use Kezi\Sales\Filament\Clusters\Sales\SalesCluster;
use Kezi\Sales\Models\Quote;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $cluster = SalesCluster::class;

    protected static ?string $recordTitleAttribute = 'quote_number';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return QuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('sales::quote.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('sales::quote.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sales::quote.model.plural_label');
    }
}
