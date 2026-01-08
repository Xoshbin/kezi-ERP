<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\QualityControl\Enums\QualityAlertStatus;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;
use Modules\QualityControl\Filament\Clusters\QualityControlCluster;
use Modules\QualityControl\Models\QualityAlert;

class QualityAlertResource extends Resource
{
    protected static ?string $model = QualityAlert::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $cluster = QualityControlCluster::class;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('quality::alert.navigation_label');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', QualityAlertStatus::New)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('quality::alert.section_basic'))
                    ->schema([
                        Forms\Components\TextInput::make('number')
                            ->label(__('quality::alert.number'))
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label(__('quality::alert.status'))
                            ->options(collect(QualityAlertStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('product_id')
                            ->label(__('quality::alert.product'))
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\Select::make('defect_type_id')
                            ->label(__('quality::alert.defect_type'))
                            ->relationship('defectType', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label(__('quality::alert.description'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('assigned_to_user_id')
                            ->label(__('quality::alert.assigned_to'))
                            ->relationship('assignedToUser', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Section::make(__('quality::alert.section_capa'))
                    ->schema([
                        Forms\Components\Textarea::make('root_cause')
                            ->label(__('quality::alert.root_cause'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('corrective_action')
                            ->label(__('quality::alert.corrective_action'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('preventive_action')
                            ->label(__('quality::alert.preventive_action'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('quality::alert.number'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('quality::alert.product'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('defectType.name')
                    ->label(__('quality::alert.defect_type'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('quality::alert.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedToUser.name')
                    ->label(__('quality::alert.assigned_to'))
                    ->sortable()
                    ->placeholder(__('Unassigned')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('quality::alert.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('quality::alert.status'))
                    ->options(collect(QualityAlertStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),

                Tables\Filters\SelectFilter::make('assigned_to_user_id')
                    ->label(__('quality::alert.assigned_to'))
                    ->relationship('assignedToUser', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    // Alerts can be deleted if necessary
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityAlerts::route('/'),
            'create' => Pages\CreateQualityAlert::route('/create'),
            'edit' => Pages\EditQualityAlert::route('/{record}/edit'),
        ];
    }
}
