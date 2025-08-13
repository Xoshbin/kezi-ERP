<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PdfSettingsResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 50;

    // Disable tenant scoping since this resource manages the Company model directly
    protected static bool $isScopedToTenant = false;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('PDF Template Settings'))
                    ->description(__('Configure how your invoices and documents will appear in PDF format'))
                    ->schema([
                        Forms\Components\Select::make('pdf_template')
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

                        Forms\Components\FileUpload::make('pdf_logo_path')
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

                Forms\Components\Section::make(__('Advanced PDF Settings'))
                    ->description(__('Customize advanced PDF generation options'))
                    ->schema([
                        Forms\Components\KeyValue::make('pdf_settings')
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Company Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pdf_template')
                    ->label(__('PDF Template'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'classic' => 'primary',
                        'modern' => 'success',
                        'minimal' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\ImageColumn::make('pdf_logo_path')
                    ->label(__('Logo'))
                    ->disk('public')
                    ->height(40)
                    ->defaultImageUrl(url('/images/placeholder-logo.png')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pdf_template')
                    ->label(__('Template'))
                    ->options([
                        'classic' => __('Classic'),
                        'modern' => __('Modern'),
                        'minimal' => __('Minimal'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
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
            'index' => \App\Filament\Clusters\Settings\Resources\PdfSettingsResource\Pages\ListPdfSettings::route('/'),
            'edit' => \App\Filament\Clusters\Settings\Resources\PdfSettingsResource\Pages\EditPdfSettings::route('/{record}/edit'),
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
