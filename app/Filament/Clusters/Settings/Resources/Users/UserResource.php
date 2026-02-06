<?php

namespace App\Filament\Clusters\Settings\Resources\Users;

use App\Filament\Clusters\Settings\Resources\Users\Pages\CreateUser;
use App\Filament\Clusters\Settings\Resources\Users\Pages\EditUser;
use App\Filament\Clusters\Settings\Resources\Users\Pages\ListUsers;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('foundation::navigation.groups.general_settings');
    }

    protected static ?string $cluster = SettingsCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('user.plural');
    }

    public static function getModelLabel(): string
    {
        return __('user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('user.form.basic_information'))
                    ->schema([
                        Select::make('company_id')
                            ->relationship('companies', 'name')
                            ->label(__('user.form.company.label')),
                        TextInput::make('name')
                            ->label(__('user.form.name.label'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('user.form.email.label'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at')
                            ->label(__('user.form.email_verified_at.label')),
                        TextInput::make('password')
                            ->label(__('user.form.password.label'))
                            ->password()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('user.column.company.name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('user.column.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('user.column.email'))
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label(__('user.column.email_verified_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('user.column.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('user.column.updated_at'))
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
