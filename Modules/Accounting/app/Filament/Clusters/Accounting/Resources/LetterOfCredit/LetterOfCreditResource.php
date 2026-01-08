<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\RelationManagers;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Widgets;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Enums\LetterOfCredit\LCType;
use Modules\Payment\Models\LetterOfCredit;
use Modules\Payment\Services\LetterOfCredit\LetterOfCreditService;

class LetterOfCreditResource extends Resource
{
    protected static ?string $model = LetterOfCredit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = AccountingCluster::class;

    protected static ?string $recordTitleAttribute = 'lc_number';

    public static function getNavigationGroup(): ?string
    {
        return 'LC Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('LC Details')
                    ->schema([
                        Group::make([
                            ToggleButtons::make('type')
                                ->options(LCType::class)
                                ->inline()
                                ->default(LCType::Import->value)
                                ->required(),

                            Select::make('vendor_id')
                                ->relationship('vendor', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Beneficiary (Vendor)'),
                        ])->columnSpanFull(),

                        Group::make([
                            TextInput::make('amount')
                                ->label('LC Amount')
                                ->required()
                                ->rule('numeric')
                                ->prefix('IQD')
                                ->afterStateHydrated(fn (TextInput $component, $state) => $component->state($state instanceof \Brick\Money\Money ? $state->getAmount()->toFloat() : $state)),

                            Select::make('currency_id')
                                ->relationship('currency', 'code')
                                ->required()
                                ->default(1),

                            Select::make('purchase_order_id')
                                ->relationship('purchaseOrder', 'po_number')
                                ->searchable()
                                ->label('Purchase Order'),

                            Select::make('issuing_bank_partner_id')
                                ->relationship('issuingBank', 'name')
                                ->searchable()
                                ->label('Issuing Bank'),
                        ])->columns(2),

                        Group::make([
                            DatePicker::make('issue_date')
                                ->required()
                                ->default(now())
                                ->label('Issue Date'),

                            DatePicker::make('expiry_date')
                                ->required()
                                ->default(now()->addMonths(3))
                                ->label('Expiry Date'),

                            DatePicker::make('shipment_date')
                                ->label('Latest Shipment Date'),

                            TextInput::make('incoterm')
                                ->label('Incoterm')
                                ->placeholder('e.g., FOB, CIF, DDP'),
                        ])->columns(2),

                        Textarea::make('terms_and_conditions')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lc_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Beneficiary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (LetterOfCredit $record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->money(fn (LetterOfCredit $record) => $record->currency->code)
                    ->color(fn (LetterOfCredit $record) => $record->balance->isZero() ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn (LetterOfCredit $record) => $record->expiry_date->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('type')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LCStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->options(LCType::class),
            ])
            ->actions([
                EditAction::make(),

                // Issue LC Action
                Action::make('issue')
                    ->label('Issue')
                    ->icon('heroicon-m-document-check')
                    ->color('info')
                    ->form([
                        TextInput::make('bank_reference')
                            ->label('Bank Reference Number')
                            ->required(),
                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->visible(fn (LetterOfCredit $record) => $record->status === LCStatus::Draft)
                    ->action(function (LetterOfCredit $record, array $data) {
                        $dto = new \Modules\Payment\DataTransferObjects\LetterOfCredit\IssueLetterOfCreditDTO(
                            bank_reference: $data['bank_reference'],
                            issue_date: $data['issue_date']
                        );
                        app(LetterOfCreditService::class)->issue($record, $dto, auth()->user());
                    }),

                // Cancel LC Action
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LetterOfCredit $record) => $record->status === LCStatus::Draft || $record->status === LCStatus::Issued)
                    ->action(function (LetterOfCredit $record) {
                        app(LetterOfCreditService::class)->cancel($record, auth()->user());
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UtilizationsRelationManager::class,
            RelationManagers\ChargesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterOfCredits::route('/'),
            'create' => Pages\CreateLetterOfCredit::route('/create'),
            'edit' => Pages\EditLetterOfCredit::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\UpcomingLCExpirations::class,
        ];
    }
}
