<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PdfSettings;

use BackedEnum;
use App\Models\Company;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\Settings\SettingsCluster;
use Modules\Foundation\Filament\Clusters\Settings\Resources\PdfSettings\Pages\EditPdfSettings;
use Modules\Foundation\Filament\Clusters\Settings\Resources\PdfSettings\Pages\ListPdfSettings;

class PdfSettingsResource extends Resource
{
    public static ?string $tenantOwnershipRelationshipName = 'users';

    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('pdf_settings.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('pdf_settings.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pdf_settings.model_plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pdf_settings.template_settings'))
                    ->description(__('pdf_settings.template_settings_description'))
                    ->schema([
                        Select::make('pdf_template')
                            ->label(__('pdf_settings.default_pdf_template'))
                            ->options([
                                'classic' => __('pdf_settings.classic_template'),
                                'modern' => __('pdf_settings.modern_template'),
                                'minimal' => __('pdf_settings.minimal_template'),
                            ])
                            ->default('classic')
                            ->required()
                            ->rules(['required', 'in:classic,modern,minimal'])
                            ->helperText(__('pdf_settings.template_help')),

                        FileUpload::make('pdf_logo_path')
                            ->label(__('pdf_settings.company_logo'))
                            ->image()
                            ->disk('public')
                            ->directory('company-logos')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(2048)
                            ->helperText(__('pdf_settings.logo_help')),
                    ]),

                Section::make(__('pdf_settings.advanced_settings'))
                    ->description(__('pdf_settings.advanced_settings_description'))
                    ->schema([
                        KeyValue::make('pdf_settings')
                            ->label(__('pdf_settings.custom_pdf_settings'))
                            ->keyLabel(__('pdf_settings.setting_name'))
                            ->valueLabel(__('pdf_settings.setting_value'))
                            ->addActionLabel(__('pdf_settings.add_setting'))
                            ->helperText(__('pdf_settings.settings_help'))
                            ->default([
                                'font_size' => '12',
                                'margin_top' => '20',
                                'margin_bottom' => '20',
                                'margin_left' => '15',
                                'margin_right' => '15',
                                'show_company_logo' => 'true',
                                'show_footer' => 'true',
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('pdf_settings.company_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pdf_template')
                    ->label(__('pdf_settings.pdf_template'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'classic' => 'primary',
                        'modern' => 'success',
                        'minimal' => 'warning',
                        default => 'gray',
                    }),

                ImageColumn::make('pdf_logo_path')
                    ->label(__('pdf_settings.logo'))
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(url('/images/placeholder-logo.png')),

                TextColumn::make('updated_at')
                    ->label(__('pdf_settings.last_updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('pdf_template')
                    ->label(__('pdf_settings.template'))
                    ->options([
                        'classic' => __('pdf_settings.classic'),
                        'modern' => __('pdf_settings.modern'),
                        'minimal' => __('pdf_settings.minimal'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions for PDF settings
            ]);
    }

    /**
     * @return Builder<Company>
     */
    public static function getEloquentQuery(): Builder
    {
        // Only show the current tenant company
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()->where('id', $tenant instanceof Company ? $tenant->getKey() : null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPdfSettings::route('/'),
            'edit' => EditPdfSettings::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // PDF settings are edited, not created
    }

    public static function canDelete(mixed $record): bool
    {
        return false; // PDF settings cannot be deleted
    }
}
