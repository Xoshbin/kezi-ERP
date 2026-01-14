<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;
use Modules\Accounting\Models\DunningLevel;

class DunningLevelResource extends Resource
{
    protected static ?string $model = DunningLevel::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.accounting_settings');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::dunning_level.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::dunning_level.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::dunning_level.sections.general_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('accounting::dunning_level.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('days_overdue')
                            ->label(__('accounting::dunning_level.fields.days_overdue'))
                            ->helperText(__('accounting::dunning_level.helpers.days_overdue'))
                            ->numeric()
                            ->required()
                            ->default(0),
                        Toggle::make('send_email')
                            ->label(__('accounting::dunning_level.fields.send_email'))
                            ->default(true)
                            ->live(),
                        Toggle::make('print_letter')
                            ->label(__('accounting::dunning_level.fields.print_letter'))
                            ->default(false),
                    ])->columns(2),

                Section::make(__('accounting::dunning_level.sections.late_fee_configuration'))
                    ->schema([
                        Toggle::make('charge_fee')
                            ->label(__('accounting::dunning_level.fields.charge_fee'))
                            ->default(false)
                            ->live(),

                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\Select::make('fee_product_id')
                                    ->label(__('accounting::dunning_level.fields.fee_product'))
                                    ->relationship('feeProduct', 'name')
                                    ->requiredIf('charge_fee', true)
                                    ->searchable(),

                                \Filament\Forms\Components\TextInput::make('fee_amount')
                                    ->label(__('accounting::dunning_level.fields.fee_amount'))
                                    ->rules(['numeric'])
                                    ->extraInputAttributes(['type' => 'number', 'step' => '0.01'])
                                    ->prefix('MC') // Using generic currency symbol as place holder
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => $state instanceof \Brick\Money\Money ? $state->getAmount()->toFloat() : $state),

                                \Filament\Forms\Components\TextInput::make('fee_percentage')
                                    ->label(__('accounting::dunning_level.fields.fee_percentage'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0),
                            ])
                            ->visible(fn (callable $get) => $get('charge_fee')),
                    ]),

                Section::make(__('accounting::dunning_level.sections.email_configuration'))
                    ->description(__('accounting::dunning_level.helpers.email_configuration'))
                    ->schema([
                        TextInput::make('email_subject')
                            ->label(__('accounting::dunning_level.fields.email_subject'))
                            ->requiredIf('send_email', true),
                        Textarea::make('email_body')
                            ->label(__('accounting::dunning_level.fields.email_body'))
                            ->rows(5)
                            ->requiredIf('send_email', true),
                    ])
                    ->visible(fn (callable $get) => $get('send_email')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('accounting::dunning_level.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->label(__('accounting::dunning_level.fields.days_overdue'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('send_email')
                    ->label(__('accounting::dunning_level.fields.send_email'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('print_letter')
                    ->label(__('accounting::dunning_level.fields.print_letter'))
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDunningLevels::route('/'),
            'create' => Pages\CreateDunningLevel::route('/create'),
            'edit' => Pages\EditDunningLevel::route('/{record}/edit'),
        ];
    }
}
