<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
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

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.administration');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::fiscal_position.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::fiscal_position.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::fiscal_position.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                TextInput::make('name')
                    ->label(__('accounting::fiscal_position.name'))
                    ->required()
                    ->maxLength(255),
                Section::make(__('accounting::fiscal_position.criteria'))
                    ->description(__('accounting::fiscal_position.criteria_description'))
                    ->schema([
                        Toggle::make('auto_apply')
                            ->label(__('accounting::fiscal_position.auto_apply'))
                            ->live(),
                        Toggle::make('vat_required')
                            ->label(__('accounting::fiscal_position.vat_required'))
                            ->visible(fn ($get) => $get('auto_apply')),
                        TextInput::make('country')
                            ->label(__('accounting::fiscal_position.country'))
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('auto_apply')),
                        TextInput::make('zip_from')
                            ->label(__('accounting::fiscal_position.zip_from'))
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('auto_apply')),
                        TextInput::make('zip_to')
                            ->label(__('accounting::fiscal_position.zip_to'))
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('auto_apply')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('accounting::fiscal_position.company'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('accounting::fiscal_position.name'))
                    ->searchable(),
                ToggleColumn::make('auto_apply')
                    ->label(__('accounting::fiscal_position.auto_apply')),
                TextColumn::make('country')
                    ->label(__('accounting::fiscal_position.country'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('accounting::fiscal_position.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::fiscal_position.updated_at'))
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}
