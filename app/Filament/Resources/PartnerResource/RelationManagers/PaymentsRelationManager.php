<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('partner.payments_relation_manager.title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->label(__('partner.payments_relation_manager.payment_date'))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('partner.payments_relation_manager.amount'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('payment_type')
                    ->label(__('partner.payments_relation_manager.payment_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference')
                    ->label(__('partner.payments_relation_manager.reference'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->label(__('partner.payments_relation_manager.status'))
                    ->required()
                    ->maxLength(255)
                    ->default('Draft'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('partner.payments_relation_manager.payment_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('partner.payments_relation_manager.amount')),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label(__('partner.payments_relation_manager.payment_type')),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('partner.payments_relation_manager.reference')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('partner.payments_relation_manager.status')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
