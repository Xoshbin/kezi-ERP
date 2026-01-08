<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\QualityControl\Enums\QualityTriggerFrequency;
use Modules\QualityControl\Enums\QualityTriggerOperation;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages;
use Modules\QualityControl\Filament\Clusters\QualityControlCluster;
use Modules\QualityControl\Models\QualityControlPoint;

class QualityControlPointResource extends Resource
{
    protected static ?string $model = QualityControlPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $cluster = QualityControlCluster::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('quality::control_point.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('quality::control_point.section_basic'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('quality::control_point.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('trigger_operation')
                            ->label(__('quality::control_point.trigger_operation'))
                            ->options(collect(QualityTriggerOperation::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('trigger_frequency')
                            ->label(__('quality::control_point.trigger_frequency'))
                            ->options(collect(QualityTriggerFrequency::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('product_id')
                            ->label(__('quality::control_point.product'))
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText(__('quality::control_point.product_helper'))
                            ->columnSpan(1),

                        Forms\Components\Select::make('inspection_template_id')
                            ->label(__('quality::control_point.inspection_template'))
                            ->relationship('inspectionTemplate', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('quantity_threshold')
                            ->label(__('quality::control_point.quantity_threshold'))
                            ->numeric()
                            ->nullable()
                            ->helperText(__('quality::control_point.quantity_threshold_helper'))
                            ->visible(fn (Forms\Get $get) => $get('trigger_frequency') === QualityTriggerFrequency::PerQuantity->value)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_blocking')
                            ->label(__('quality::control_point.is_blocking'))
                            ->helperText(__('quality::control_point.is_blocking_helper'))
                            ->default(false)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('active')
                            ->label(__('quality::control_point.active'))
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('quality::control_point.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trigger_operation')
                    ->label(__('quality::control_point.trigger_operation'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('trigger_frequency')
                    ->label(__('quality::control_point.trigger_frequency'))
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('quality::control_point.product'))
                    ->placeholder(__('All products'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_blocking')
                    ->label(__('quality::control_point.is_blocking'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('quality::control_point.active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_operation')
                    ->label(__('quality::control_point.trigger_operation'))
                    ->options(collect(QualityTriggerOperation::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),

                Tables\Filters\TernaryFilter::make('is_blocking')
                    ->label(__('quality::control_point.is_blocking')),

                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('quality::control_point.active')),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityControlPoints::route('/'),
            'create' => Pages\CreateQualityControlPoint::route('/create'),
            'edit' => Pages\EditQualityControlPoint::route('/{record}/edit'),
        ];
    }
}
