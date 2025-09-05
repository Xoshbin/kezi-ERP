<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Enums\Settings\NumberingType;
use App\Filament\Clusters\Settings\Resources\NumberingSettingsResource\Pages\EditNumberingSettings;
use App\Filament\Clusters\Settings\Resources\NumberingSettingsResource\Pages\ListNumberingSettings;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Company;
use App\Rules\NumberingSettingsChangeRule;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NumberingSettingsResource extends Resource
{
    public static ?string $tenantOwnershipRelationshipName = 'users';

    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = SettingsCluster::class;

    public static function getModelLabel(): string
    {
        return __('numbering.settings.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('numbering.settings.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('numbering.settings.invoice_numbering'))
                ->description(__('numbering.settings.invoice_numbering_description'))
                ->schema([
                    Select::make('numbering_settings.invoice.type')
                        ->label(__('numbering.settings.numbering_type'))
                        ->options(NumberingType::getFilamentOptions('INV'))
                        ->default(NumberingType::SLASH_YEAR_MONTH->value)
                        ->required()
                        ->rules([
                            'required',
                            function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant instanceof \App\Models\Company) {
                                    throw new \Exception('Company not found');
                                }
                                return new NumberingSettingsChangeRule($tenant);
                            },
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            if ($state) {
                                $prefix = $get('numbering_settings.invoice.prefix') ?? 'INV';
                                $example = NumberingType::from($state)->getExample($prefix);
                                $set('invoice_example', $example);
                            }
                        })
                        ->columnSpan(2),

                    TextInput::make('numbering_settings.invoice.prefix')
                        ->label(__('numbering.settings.prefix'))
                        ->helperText(__('numbering.settings.prefix_help'))
                        ->default('INV')
                        ->required()
                        ->maxLength(10)
                        ->rules([
                            'required',
                            'max:10',
                            function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant instanceof \App\Models\Company) {
                                    throw new \Exception('Company not found');
                                }
                                return new NumberingSettingsChangeRule($tenant);
                            },
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $type = $get('numbering_settings.invoice.type') ?? NumberingType::SLASH_YEAR_MONTH->value;
                            if ($state && $type) {
                                $example = NumberingType::from($type)->getExample($state);
                                $set('invoice_example', $example);
                            }
                        })
                        ->columnSpan(1),

                    TextInput::make('numbering_settings.invoice.padding')
                        ->label(__('numbering.settings.padding'))
                        ->helperText(__('numbering.settings.padding_help'))
                        ->numeric()
                        ->default(7)
                        ->required()
                        ->minValue(3)
                        ->maxValue(10)
                        ->rules([
                            'required',
                            'integer',
                            'min:3',
                            'max:10',
                            function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant instanceof \App\Models\Company) {
                                    throw new \Exception('Company not found');
                                }
                                return new NumberingSettingsChangeRule($tenant);
                            },
                        ])
                        ->columnSpan(1),

                    ViewField::make('invoice_example')
                        ->label(__('numbering.settings.current_format'))
                        ->view('filament.forms.components.example-display')
                        ->viewData(function (callable $get) {
                            $type = $get('numbering_settings.invoice.type') ?? NumberingType::SLASH_YEAR_MONTH->value;
                            $prefix = $get('numbering_settings.invoice.prefix') ?? 'INV';

                            return ['example' => NumberingType::from($type)->getExample($prefix)];
                        })
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('numbering.settings.bill_numbering'))
                ->description(__('numbering.settings.bill_numbering_description'))
                ->schema([
                    Select::make('numbering_settings.vendor_bill.type')
                        ->label(__('numbering.settings.numbering_type'))
                        ->options(NumberingType::getFilamentOptions('BILL'))
                        ->default(NumberingType::SLASH_YEAR_MONTH->value)
                        ->required()
                        ->rules([
                            'required',
                            function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant instanceof \App\Models\Company) {
                                    throw new \Exception('Company not found');
                                }
                                return new NumberingSettingsChangeRule($tenant);
                            },
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            if ($state) {
                                $prefix = $get('numbering_settings.vendor_bill.prefix') ?? 'BILL';
                                $example = NumberingType::from($state)->getExample($prefix);
                                $set('bill_example', $example);
                            }
                        })
                        ->columnSpan(2),

                    TextInput::make('numbering_settings.vendor_bill.prefix')
                        ->label(__('numbering.settings.prefix'))
                        ->helperText(__('numbering.settings.prefix_help'))
                        ->default('BILL')
                        ->required()
                        ->maxLength(10)
                        ->rules([
                            'required',
                            'max:10',
                            function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant instanceof \App\Models\Company) {
                                    throw new \Exception('Company not found');
                                }
                                return new NumberingSettingsChangeRule($tenant);
                            },
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $type = $get('numbering_settings.vendor_bill.type') ?? NumberingType::SLASH_YEAR_MONTH->value;
                            if ($state && $type) {
                                $example = NumberingType::from($type)->getExample($state);
                                $set('bill_example', $example);
                            }
                        })
                        ->columnSpan(1),

                    TextInput::make('numbering_settings.vendor_bill.padding')
                        ->label(__('numbering.settings.padding'))
                        ->helperText(__('numbering.settings.padding_help'))
                        ->numeric()
                        ->default(7)
                        ->required()
                        ->minValue(3)
                        ->maxValue(10)
                        ->rules([
                            'required',
                            'integer',
                            'min:3',
                            'max:10',
                            function () {
                                $tenant = Filament::getTenant();
                                if (!$tenant instanceof \App\Models\Company) {
                                    throw new \Exception('Company not found');
                                }
                                return new NumberingSettingsChangeRule($tenant);
                            },
                        ])
                        ->columnSpan(1),

                    ViewField::make('bill_example')
                        ->label(__('numbering.settings.current_format'))
                        ->view('filament.forms.components.example-display')
                        ->viewData(function (callable $get) {
                            $type = $get('numbering_settings.vendor_bill.type') ?? NumberingType::SLASH_YEAR_MONTH->value;
                            $prefix = $get('numbering_settings.vendor_bill.prefix') ?? 'BILL';

                            return ['example' => NumberingType::from($type)->getExample($prefix)];
                        })
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('company.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('invoice_numbering_format')
                    ->label(__('numbering.settings.invoice_numbering'))
                    ->getStateUsing(function (Company $record): string {
                        $config = $record->getInvoiceNumberingConfig();
                        $type = NumberingType::from($config['type']);

                        return $type->getExample($config['prefix']);
                    })
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-document-text'),

                TextColumn::make('bill_numbering_format')
                    ->label(__('numbering.settings.bill_numbering'))
                    ->getStateUsing(function (Company $record): string {
                        $config = $record->getVendorBillNumberingConfig();
                        $type = NumberingType::from($config['type']);

                        return $type->getExample($config['prefix']);
                    })
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-document'),

                IconColumn::make('can_change_numbering')
                    ->label(__('numbering.settings.can_change'))
                    ->getStateUsing(fn (Company $record): bool => $record->canChangeNumberingSettings())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('updated_at')
                    ->label(__('general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->before(function (Company $record) {
                        if (! $record->canChangeNumberingSettings()) {
                            $errors = $record->getNumberingChangeValidationErrors();
                            Notification::make()
                                ->title(__('numbering.settings.cannot_change_title'))
                                ->body(__('numbering.settings.cannot_change_message').' ('.implode(', ', $errors).')')
                                ->danger()
                                ->send();

                            return false;
                        }
                    }),
            ])
            ->toolbarActions([
                // No bulk actions for numbering settings
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show the current tenant company
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()->where('id', $tenant instanceof \App\Models\Company ? $tenant->getKey() : null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNumberingSettings::route('/'),
            'edit' => EditNumberingSettings::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Cannot create new companies from this resource
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Cannot delete companies from this resource
    }
}
