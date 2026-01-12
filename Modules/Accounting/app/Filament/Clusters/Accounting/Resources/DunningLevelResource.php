<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources;

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
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;
use Modules\Accounting\Models\DunningLevel;

class DunningLevelResource extends Resource
{
    protected static ?string $model = DunningLevel::class;

    protected static ?string $cluster = AccountingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'Configuration';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('days_overdue')
                            ->label('Days Overdue')
                            ->helperText('Number of days after due date to trigger this level')
                            ->numeric()
                            ->required()
                            ->default(0),
                        Toggle::make('send_email')
                            ->label('Send Email')
                            ->default(true)
                            ->live(),
                        Toggle::make('print_letter')
                            ->label('Print Letter')
                            ->default(false),
                    ])->columns(2),

                Section::make('Late Fee Configuration')
                    ->schema([
                        Toggle::make('charge_fee')
                            ->label('Charge Late Fee')
                            ->default(false)
                            ->live(),

                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\Select::make('fee_product_id')
                                    ->label('Fee Product')
                                    ->relationship('feeProduct', 'name')
                                    ->requiredIf('charge_fee', true)
                                    ->searchable(),

                                \Filament\Forms\Components\TextInput::make('fee_amount')
                                    ->label('Flat Fee Amount')
                                    ->rules(['numeric'])
                                    ->extraInputAttributes(['type' => 'number', 'step' => '0.01'])
                                    ->prefix('MC') // Using generic currency symbol as place holder
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => $state instanceof \Brick\Money\Money ? $state->getAmount()->toFloat() : $state),

                                \Filament\Forms\Components\TextInput::make('fee_percentage')
                                    ->label('Fee Percentage')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0),
                            ])
                            ->visible(fn (callable $get) => $get('charge_fee')),
                    ]),

                Section::make('Email Configuration')
                    ->description('Configure the email template explicitly.')
                    ->schema([
                        TextInput::make('email_subject')
                            ->label('Email Subject')
                            ->requiredIf('send_email', true),
                        Textarea::make('email_body')
                            ->label('Email Body')
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_overdue')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('send_email')
                    ->boolean(),
                Tables\Columns\IconColumn::make('print_letter')
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
