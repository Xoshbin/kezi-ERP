<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Enums\PaymentTerms\PaymentTermType;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Clusters\Settings\Resources\PaymentTermResource\Pages;
use App\Models\PaymentTerm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentTermResource extends Resource
{
    protected static ?string $model = PaymentTerm::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('payment_terms.actions.create');
    }

    public static function getModelLabel(): string
    {
        return __('payment_terms.fields.name');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payment_terms.fields.lines');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payment_terms.fields.name'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('payment_terms.fields.name'))
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('payment_terms.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label(__('payment_terms.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make(__('payment_terms.fields.lines'))
                    ->schema([
                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                TextInput::make('sequence')
                                    ->label(__('payment_terms.fields.sequence'))
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1),

                                Select::make('type')
                                    ->label(__('payment_terms.fields.type'))
                                    ->options(PaymentTermType::class)
                                    ->required(),

                                TextInput::make('days')
                                    ->label(__('payment_terms.fields.days'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                TextInput::make('day_of_month')
                                    ->label(__('payment_terms.fields.day_of_month'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31),

                                TextInput::make('percentage')
                                    ->label(__('payment_terms.fields.percentage'))
                                    ->numeric()
                                    ->default(100)
                                    ->required()
                                    ->minValue(0.01)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                TextInput::make('discount_percentage')
                                    ->label(__('payment_terms.fields.discount_percentage'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                TextInput::make('discount_days')
                                    ->label(__('payment_terms.fields.discount_days'))
                                    ->numeric()
                                    ->minValue(0),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel(__('payment_terms.actions.add_line'))
                            ->reorderable(true)
                            ->orderColumn('sequence'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('payment_terms.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('payment_terms.fields.description'))
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('lines_count')
                    ->label(__('payment_terms.fields.lines'))
                    ->counts('lines')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('payment_terms.fields.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('payment_terms.fields.is_active')),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['lines']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentTerms::route('/'),
            'create' => Pages\CreatePaymentTerm::route('/create'),
            'edit' => Pages\EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
