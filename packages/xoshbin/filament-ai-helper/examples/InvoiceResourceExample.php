<?php

namespace App\Filament\Resources;

use Xoshbin\FilamentAiHelper\Concerns\HasAiHelper;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}

// Example of using the AI Helper in resource pages
namespace App\Filament\Resources\InvoiceResource\Pages;

use Xoshbin\FilamentAiHelper\Concerns\HasAiHelper;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    use HasAiHelper;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return $this->addAiHelperToHeaderActions([
            Actions\DeleteAction::make(),
        ]);
    }

    /**
     * Custom authorization for AI Helper
     */
    protected function canUseAiHelper(): bool
    {
        // Only allow users with 'use-ai-assistant' permission
        return auth()->user()->can('use-ai-assistant');
    }
}

namespace App\Filament\Resources\InvoiceResource\Pages;

use Xoshbin\FilamentAiHelper\Concerns\HasAiHelper;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    use HasAiHelper;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return $this->addAiHelperToHeaderActions([
            // Add any other header actions here
        ]);
    }
}

namespace App\Filament\Resources\InvoiceResource\Pages;

use Xoshbin\FilamentAiHelper\Concerns\HasAiHelper;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    use HasAiHelper;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return $this->addAiHelperToHeaderActions([
            Actions\CreateAction::make(),
        ]);
    }
}
