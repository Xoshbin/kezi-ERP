<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions;


use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages\CreateFiscalPosition;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages\EditFiscalPosition;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages\ListFiscalPositions;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\RelationManagers\AccountMappingsRelationManager;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\RelationManagers\TaxMappingsRelationManager;
use Modules\Accounting\Models\FiscalPosition;

class FiscalPositionResource extends Resource
{
    use Translatable;

    protected static ?string $model = FiscalPosition::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('fiscal_position.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('fiscal_position.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('fiscal_position.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('fiscal_position.company'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('fiscal_position.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('country')
                    ->label(__('fiscal_position.country'))
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('fiscal_position.company'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('fiscal_position.name'))
                    ->searchable(),
                TextColumn::make('country')
                    ->label(__('fiscal_position.country'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('fiscal_position.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('fiscal_position.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TaxMappingsRelationManager::class,
            AccountMappingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiscalPositions::route('/'),
            'create' => CreateFiscalPosition::route('/create'),
            'edit' => EditFiscalPosition::route('/{record}/edit'),
        ];
    }
}
