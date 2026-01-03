<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Accounting\Enums\Accounting\FiscalYearState;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages\CreateFiscalYear;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages\EditFiscalYear;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages\ListFiscalYears;
use Modules\Accounting\Models\FiscalYear;

class FiscalYearResource extends Resource
{
    protected static ?string $model = FiscalYear::class;

    protected static ?string $cluster = AccountingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 100;

    public static function getNavigationGroup(): string
    {
        return __('accounting::navigation.configuration');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::fiscal_year.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::fiscal_year.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::fiscal_year.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::fiscal_year.section_general'))
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label(__('accounting::fiscal_year.field_name'))
                            ->required()
                            ->maxLength(255),

                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label(__('accounting::fiscal_year.field_start_date'))
                            ->required()
                            ->native(false),

                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label(__('accounting::fiscal_year.field_end_date'))
                            ->required()
                            ->native(false)
                            ->afterOrEqual('start_date'),

                        \Filament\Forms\Components\Toggle::make('generate_periods')
                            ->label(__('accounting::fiscal_year.field_generate_periods'))
                            ->helperText(__('accounting::fiscal_year.field_generate_periods_help'))
                            ->default(false)
                            ->visibleOn('create'),
                    ]),
            ]);
    }

    /**
     * @return Builder<FiscalYear>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', filament()->getTenant()?->id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('accounting::fiscal_year.field_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('accounting::fiscal_year.field_start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('accounting::fiscal_year.field_end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('state')
                    ->label(__('accounting::fiscal_year.field_state'))
                    ->badge()
                    ->formatStateUsing(fn (FiscalYearState $state): string => $state->label())
                    ->color(fn (FiscalYearState $state): string => $state->color()),

                TextColumn::make('closed_at')
                    ->label(__('accounting::fiscal_year.field_closed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('periods_count')
                    ->label(__('accounting::fiscal_year.field_periods_count'))
                    ->counts('periods')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('accounting::fiscal_year.field_state'))
                    ->options(collect(FiscalYearState::cases())->mapWithKeys(
                        fn (FiscalYearState $state) => [$state->value => $state->label()]
                    )),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (FiscalYear $record) {
                        if ($record->isClosed()) {
                            Notification::make()
                                ->title(__('accounting::fiscal_year.cannot_delete_closed'))
                                ->danger()
                                ->send();

                            return false;
                        }

                        return true;
                    }),
            ])
            ->defaultSort('start_date', 'desc');
    }

    /**
     * @return array<string>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListFiscalYears::route('/'),
            'create' => CreateFiscalYear::route('/create'),
            'edit' => EditFiscalYear::route('/{record}/edit'),
        ];
    }
}
