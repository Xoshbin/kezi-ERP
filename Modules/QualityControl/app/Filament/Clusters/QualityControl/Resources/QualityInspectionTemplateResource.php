<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\QualityControl\Enums\QualityCheckType;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;
use Modules\QualityControl\Filament\Clusters\QualityControlCluster;
use Modules\QualityControl\Models\QualityInspectionTemplate;

class QualityInspectionTemplateResource extends Resource
{
    protected static ?string $model = QualityInspectionTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = QualityControlCluster::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('quality::template.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('quality::template.section_basic'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('quality::template.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('quality::template.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('active')
                            ->label(__('quality::template.active'))
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make(__('quality::template.section_parameters'))
                    ->schema([
                        Forms\Components\Repeater::make('parameters')
                            ->label(__('quality::template.parameters'))
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('quality::template.parameter_name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\Select::make('check_type')
                                    ->label(__('quality::template.check_type'))
                                    ->options(collect(QualityCheckType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('min_value')
                                    ->label(__('quality::template.min_value'))
                                    ->numeric()
                                    ->nullable()
                                    ->visible(fn ($get) => $get('check_type') === QualityCheckType::Measure->value)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('max_value')
                                    ->label(__('quality::template.max_value'))
                                    ->numeric()
                                    ->nullable()
                                    ->visible(fn ($get) => $get('check_type') === QualityCheckType::Measure->value)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_of_measure')
                                    ->label(__('quality::template.unit_of_measure'))
                                    ->maxLength(50)
                                    ->nullable()
                                    ->visible(fn ($get) => $get('check_type') === QualityCheckType::Measure->value)
                                    ->columnSpan(2),

                                Forms\Components\Textarea::make('instructions')
                                    ->label(__('quality::template.instructions'))
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Hidden::make('sequence')
                                    ->default(fn ($get) => $get('../../parameters') ? count($get('../../parameters')) : 0),
                            ])
                            ->columns(4)
                            ->reorderable()
                            ->orderColumn('sequence')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel(__('quality::template.add_parameter'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('quality::template.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parameters_count')
                    ->label(__('quality::template.parameters_count'))
                    ->counts('parameters')
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('quality::template.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('quality::template.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('quality::template.active')),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityInspectionTemplates::route('/'),
            'create' => Pages\CreateQualityInspectionTemplate::route('/create'),
            'edit' => Pages\EditQualityInspectionTemplate::route('/{record}/edit'),
        ];
    }
}
