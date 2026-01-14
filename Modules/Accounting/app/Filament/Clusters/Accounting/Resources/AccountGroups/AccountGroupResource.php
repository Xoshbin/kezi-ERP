<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages\CreateAccountGroup;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages\EditAccountGroup;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages\ListAccountGroups;
use Modules\Accounting\Models\AccountGroup;

class AccountGroupResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    use Translatable;

    protected static ?string $model = AccountGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 4;

    public static function getPluralModelLabel(): string
    {
        return __('accounting::account_group.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::account_group.plural_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('accounting::navigation.groups.accounting_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::account_group.basic_information'))
                    ->description(__('accounting::account_group.basic_information_description'))
                    ->schema([
                        TextInput::make('code_prefix_start')
                            ->label(__('accounting::account_group.code_prefix_start'))
                            ->required()
                            ->maxLength(10)
                            ->helperText(__('accounting::account_group.code_prefix_start_help')),
                        TextInput::make('code_prefix_end')
                            ->label(__('accounting::account_group.code_prefix_end'))
                            ->required()
                            ->maxLength(10)
                            ->helperText(__('accounting::account_group.code_prefix_end_help')),
                        TextInput::make('name')
                            ->label(__('accounting::account_group.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('parent_id')
                            ->label(__('accounting::account_group.parent'))
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('level')
                            ->label(__('accounting::account_group.level'))
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code_prefix_start')
                    ->label(__('accounting::account_group.code_prefix_start'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code_prefix_end')
                    ->label(__('accounting::account_group.code_prefix_end'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('accounting::account_group.name'))
                    ->searchable(),
                TextColumn::make('level')
                    ->label(__('accounting::account_group.level'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('accounts_count')
                    ->label(__('accounting::account_group.accounts_count'))
                    ->counts('accounts'),
                TextColumn::make('created_at')
                    ->label(__('accounting::account_group.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('code_prefix_start')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccountGroups::route('/'),
            'create' => CreateAccountGroup::route('/create'),
            'edit' => EditAccountGroup::route('/{record}/edit'),
        ];
    }
}
