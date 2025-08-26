<?php

namespace App\Filament\Clusters\Settings\Resources\PdfSettings;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use App\Filament\Clusters\Settings\Resources\PdfSettings\Pages\ListPdfSettings;
use App\Filament\Clusters\Settings\Resources\PdfSettings\Pages\EditPdfSettings;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Company;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PdfSettingsResource extends Resource
{
    public static null|string $tenantOwnershipRelationshipName = 'users';

    protected static ?string $model = Company::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-printer';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('PDF Settings');
    }

    public static function getModelLabel(): string
    {
        return __('PDF Settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('PDF Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('PDF Template Settings'))
                    ->description(__('Configure how your invoices and documents will appear in PDF format'))
                    ->schema([
                        Select::make('pdf_template')
                            ->label(__('Default PDF Template'))
                            ->options([
                                'classic' => __('Classic Template'),
                                'modern' => __('Modern Template'),
                                'minimal' => __('Minimal Template'),
                            ])
                            ->default('classic')
                            ->required()
                            ->rules(['required', 'in:classic,modern,minimal'])
                            ->helperText(__('Choose the default template style for your PDF documents')),

                        FileUpload::make('pdf_logo_path')
                            ->label(__('Company Logo'))
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
                            ->helperText(__('Upload your company logo to appear on PDF documents (max 2MB)')),
                    ]),

                Section::make(__('Advanced PDF Settings'))
                    ->description(__('Customize advanced PDF generation options'))
                    ->schema([
                        KeyValue::make('pdf_settings')
                            ->label(__('Custom PDF Settings'))
                            ->keyLabel(__('Setting Name'))
                            ->valueLabel(__('Setting Value'))
                            ->addActionLabel(__('Add Setting'))
                            ->helperText(__('Add custom settings for PDF generation (e.g., font_size: 12, margin_top: 20)'))
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
                    ->label(__('Company Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pdf_template')
                    ->label(__('PDF Template'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'classic' => 'primary',
                        'modern' => 'success',
                        'minimal' => 'warning',
                        default => 'gray',
                    }),

                ImageColumn::make('pdf_logo_path')
                    ->label(__('Logo'))
                    ->disk('public')
                    ->height(40)
                    ->defaultImageUrl(url('/images/placeholder-logo.png')),

                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('pdf_template')
                    ->label(__('Template'))
                    ->options([
                        'classic' => __('Classic'),
                        'modern' => __('Modern'),
                        'minimal' => __('Minimal'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // No bulk actions for PDF settings
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show the current tenant company
        $tenant = \Filament\Facades\Filament::getTenant();
        return parent::getEloquentQuery()->where('id', $tenant?->id);
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

    public static function canDelete($record): bool
    {
        return false; // PDF settings cannot be deleted
    }
}
