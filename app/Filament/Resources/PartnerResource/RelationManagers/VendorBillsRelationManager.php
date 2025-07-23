<?php

namespace App\Filament\Resources\PartnerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendorBillsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorBills';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('bill_reference')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('bill_date')
                    ->required(),
                Forms\Components\DatePicker::make('accounting_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('Draft'),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bill_reference')
            ->columns([
                Tables\Columns\TextColumn::make('bill_reference'),
                Tables\Columns\TextColumn::make('bill_date')
                    ->date(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('total_amount'),
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