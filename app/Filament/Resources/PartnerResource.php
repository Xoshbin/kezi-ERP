<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Filament\Resources\PartnerResource\RelationManagers;
use App\Models\Partner;
use App\Enums\Partners\PartnerType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.sales_purchases');
    }

    public static function getLabel(): ?string
    {
        return __('partner.label');
    }

    public static function getPluralLabel(): ?string
    {
        return __('partner.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('partner.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('partner.company'))
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('partner.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label(__('partner.type'))
                    ->required()
                    ->options(
                        collect(PartnerType::cases())
                            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                    ),
                Forms\Components\TextInput::make('contact_person')
                    ->label(__('partner.contact_person'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('partner.email'))
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__('partner.phone'))
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address_line_1')
                    ->label(__('partner.address_line_1'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('address_line_2')
                    ->label(__('partner.address_line_2'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('city')
                    ->label(__('partner.city'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('state')
                    ->label(__('partner.state'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('zip_code')
                    ->label(__('partner.zip_code'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('country')
                    ->label(__('partner.country'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('tax_id')
                    ->label(__('partner.tax_id'))
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('partner.is_active'))
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_id')
                    ->label(__('partner.company'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('partner.name'))
                    ->searchable(),
                Tables\Columns\SelectColumn::make('type')
                    ->label(__('partner.type'))
                    ->searchable()
                    ->options(
                        collect(PartnerType::cases())
                            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
                    ),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label(__('partner.contact_person'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('partner.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('partner.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label(__('partner.address_line_1'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_2')
                    ->label(__('partner.address_line_2'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label(__('partner.city'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->label(__('partner.state'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('zip_code')
                    ->label(__('partner.zip_code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label(__('partner.country'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->label(__('partner.tax_id'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('partner.is_active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('partner.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('partner.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('partner.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\VendorBillsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
