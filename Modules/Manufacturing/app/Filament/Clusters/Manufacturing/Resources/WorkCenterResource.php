<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\ManufacturingCluster;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages;
use Modules\Manufacturing\Models\WorkCenter;

class WorkCenterResource extends Resource
{
    protected static ?string $model = WorkCenter::class;

    protected static ?string $cluster = ManufacturingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Work Center';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Work Centers';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Work Center Information')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Work Center Code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('hourly_cost')
                        ->label('Hourly Cost')
                        ->numeric()
                        ->prefix(fn () => auth()->user()->currentCompany->currency->symbol ?? '$')
                        ->required()
                        ->minValue(0)
                        ->helperText('Labor and overhead cost per hour'),

                    Forms\Components\TextInput::make('capacity')
                        ->label('Capacity (units/hour)')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Production capacity per hour'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hourly_cost')
                    ->label('Hourly Cost')
                    ->money(fn ($record) => $record->currency_code ?? 'USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' units/hr')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
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
            'index' => Pages\ListWorkCenters::route('/'),
            'create' => Pages\CreateWorkCenter::route('/create'),
            'edit' => Pages\EditWorkCenter::route('/{record}/edit'),
        ];
    }
}
