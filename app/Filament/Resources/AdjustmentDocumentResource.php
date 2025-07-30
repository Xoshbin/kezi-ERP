<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdjustmentDocumentResource\Pages;
use App\Filament\Resources\AdjustmentDocumentResource\RelationManagers;
use App\Models\AdjustmentDocument;
use App\Services\AdjustmentDocumentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class AdjustmentDocumentResource extends Resource
{
    protected static ?string $model = AdjustmentDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): ?string
    {
        return __('adjustment_document.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('adjustment_document.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('adjustment_document.company'))
                    ->required(),
                Forms\Components\Select::make('original_invoice_id')
                    ->relationship('originalInvoice', 'id')
                    ->label(__('adjustment_document.original_invoice')),
                Forms\Components\Select::make('original_vendor_bill_id')
                    ->relationship('originalVendorBill', 'id')
                    ->label(__('adjustment_document.original_vendor_bill')),
                Forms\Components\Select::make('journal_entry_id')
                    ->relationship('journalEntry', 'id')
                    ->label(__('adjustment_document.journal_entry')),
                Forms\Components\TextInput::make('type')
                    ->label(__('adjustment_document.type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->label(__('adjustment_document.date'))
                    ->required(),
                Forms\Components\TextInput::make('reference_number')
                    ->label(__('adjustment_document.reference_number'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('adjustment_document.total_amount'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_tax')
                    ->label(__('adjustment_document.total_tax'))
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('reason')
                    ->label(__('adjustment_document.reason'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->label(__('adjustment_document.status'))
                    ->required()
                    ->maxLength(255)
                    ->default(__('adjustment_document.status_draft')),
                Forms\Components\DateTimePicker::make('posted_at')
                    ->label(__('adjustment_document.posted_at')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('adjustment_document.company_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('originalInvoice.id')
                    ->label(__('adjustment_document.original_invoice_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('originalVendorBill.id')
                    ->label(__('adjustment_document.original_vendor_bill_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.id')
                    ->label(__('adjustment_document.journal_entry_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('adjustment_document.type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('adjustment_document.date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label(__('adjustment_document.reference_number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('adjustment_document.total_amount'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tax')
                    ->label(__('adjustment_document.total_tax'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('adjustment_document.status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('posted_at')
                    ->label(__('adjustment_document.posted_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('adjustment_document.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('adjustment_document.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('post')
                    ->label(__('adjustment_document.action_post'))
                    ->action(function (AdjustmentDocument $record) {
                        $adjustmentDocumentService = new AdjustmentDocumentService();
                        try {
                            $adjustmentDocumentService->post($record, auth()->user());
                            Notification::make()
                                ->title(__('adjustment_document.notification_posted_successfully'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('adjustment_document.notification_post_error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (AdjustmentDocument $record) => $record->status === 'Draft'),
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
            'index' => Pages\ListAdjustmentDocuments::route('/'),
            'create' => Pages\CreateAdjustmentDocument::route('/create'),
            'edit' => Pages\EditAdjustmentDocument::route('/{record}/edit'),
        ];
    }
}
