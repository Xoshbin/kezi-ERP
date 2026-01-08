<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;
use Modules\QualityControl\Filament\Clusters\QualityControlCluster;
use Modules\QualityControl\Models\DefectType;

class DefectTypeResource extends Resource
{
    protected static ?string $model = DefectType::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $cluster = QualityControlCluster::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('quality::defect_type.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label(__('quality::defect_type.code'))
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label(__('quality::defect_type.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label(__('quality::defect_type.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('active')
                            ->label(__('quality::defect_type.active'))
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
                Tables\Columns\TextColumn::make('code')
                    ->label(__('quality::defect_type.code'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('quality::defect_type.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label(__('quality::defect_type.active'))
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('quality::defect_type.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('quality::defect_type.active'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only')),
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
            'index' => Pages\ListDefectTypes::route('/'),
            'create' => Pages\CreateDefectType::route('/create'),
            'edit' => Pages\EditDefectType::route('/{record}/edit'),
        ];
    }
}
