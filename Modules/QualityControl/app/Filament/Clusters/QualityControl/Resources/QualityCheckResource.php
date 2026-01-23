<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources;

use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\QualityControl\Enums\QualityCheckStatus;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages;
use Modules\QualityControl\Filament\Clusters\QualityControlCluster;
use Modules\QualityControl\Models\QualityCheck;

class QualityCheckResource extends Resource
{
    protected static ?string $model = QualityCheck::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $cluster = QualityControlCluster::class;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('qualitycontrol::check.navigation_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('qualitycontrol::check.section_basic'))
                    ->schema([
                        TextEntry::make('number')
                            ->label(__('qualitycontrol::check.number')),
                        TextEntry::make('status')
                            ->label(__('qualitycontrol::check.status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                        TextEntry::make('product.name')
                            ->label(__('qualitycontrol::check.product')),
                        TextEntry::make('lot.lot_code')
                            ->label(__('qualitycontrol::check.lot'))
                            ->placeholder('—'),
                        TextEntry::make('is_blocking')
                            ->label(__('qualitycontrol::check.is_blocking'))
                            ->badge()
                            ->color(fn ($state) => $state ? 'danger' : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? __('qualitycontrol::check.blocking_yes') : __('qualitycontrol::check.blocking_no')),
                        TextEntry::make('notes')
                            ->label(__('qualitycontrol::check.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('qualitycontrol::check.section_basic'))
                    ->schema([
                        Forms\Components\TextInput::make('number')
                            ->label(__('qualitycontrol::check.number'))
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label(__('qualitycontrol::check.status'))
                            ->options(collect(QualityCheckStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('product_id')
                            ->label(__('qualitycontrol::check.product'))
                            ->relationship('product', 'name')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('lot_id')
                            ->label(__('qualitycontrol::check.lot'))
                            ->relationship('lot', 'lot_code')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_blocking')
                            ->label(__('qualitycontrol::check.is_blocking'))
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('qualitycontrol::check.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('qualitycontrol::check.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('qualitycontrol::check.product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lot.lot_code')
                    ->label(__('qualitycontrol::check.lot'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('qualitycontrol::check.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color())
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_blocking')
                    ->label(__('qualitycontrol::check.is_blocking'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inspectedByUser.name')
                    ->label(__('qualitycontrol::check.inspector'))
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('inspected_at')
                    ->label(__('qualitycontrol::check.inspected_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('qualitycontrol::check.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('qualitycontrol::check.status'))
                    ->options(collect(QualityCheckStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label(__('qualitycontrol::check.product'))
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    // No bulk delete for quality checks
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityChecks::route('/'),
            'view' => Pages\ViewQualityCheck::route('/{record}'),
        ];
    }
}
