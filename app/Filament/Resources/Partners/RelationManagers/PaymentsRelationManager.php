<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('partner.payments_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('payment_date')
                    ->label(__('partner.payments_relation_manager.payment_date'))
                    ->required(),
                TextInput::make('amount')
                    ->label(__('partner.payments_relation_manager.amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('payment_type')
                    ->label(__('partner.payments_relation_manager.payment_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('reference')
                    ->label(__('partner.payments_relation_manager.reference'))
                    ->maxLength(255),
                TextInput::make('status')
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
                TextColumn::make('payment_date')
                    ->label(__('partner.payments_relation_manager.payment_date'))
                    ->date(),
                TextColumn::make('amount')
                    ->label(__('partner.payments_relation_manager.amount')),
                TextColumn::make('payment_type')
                    ->label(__('partner.payments_relation_manager.payment_type')),
                TextColumn::make('reference')
                    ->label(__('partner.payments_relation_manager.reference')),
                TextColumn::make('status')
                    ->label(__('partner.payments_relation_manager.status')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
