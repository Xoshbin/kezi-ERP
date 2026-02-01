<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources;

use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;
use Kezi\QualityControl\Models\DefectType;

class DefectTypeResource extends Resource
{
    protected static ?string $model = DefectType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('qualitycontrol::defect_type.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('qualitycontrol::navigation.groups.qc_settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('code')
                        ->label(__('qualitycontrol::defect_type.code'))
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),

                    TextInput::make('name')
                        ->label(__('qualitycontrol::defect_type.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    Textarea::make('description')
                        ->label(__('qualitycontrol::defect_type.description'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Toggle::make('active')
                        ->label(__('qualitycontrol::defect_type.active'))
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
                TextColumn::make('code')
                    ->label(__('qualitycontrol::defect_type.code'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('qualitycontrol::defect_type.name'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('active')
                    ->label(__('qualitycontrol::defect_type.active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('qualitycontrol::defect_type.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label(__('qualitycontrol::defect_type.active'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only')),
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
            'index' => Pages\ListDefectTypes::route('/'),
            'create' => Pages\CreateDefectType::route('/create'),
            'edit' => Pages\EditDefectType::route('/{record}/edit'),
        ];
    }
}
