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

    public static function getModelLabel(): string
    {
        return __('accounting::lc.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::lc.plural_label');
    }

    protected static ?string $recordTitleAttribute = 'lc_number';

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::lc.navigation_group');
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
                                ->label(__('accounting::lc.beneficiary_vendor')),
                        ])->columnSpanFull(),

                        Group::make([
                            TextInput::make('amount')
                                ->label(__('accounting::lc.lc_amount'))
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
                                ->label(__('accounting::lc.purchase_order')),

                            Select::make('issuing_bank_partner_id')
                                ->relationship('issuingBank', 'name')
                                ->searchable()
                                ->label(__('accounting::lc.issuing_bank')),
                        ])->columns(2),

                        Group::make([
                            DatePicker::make('issue_date')
                                ->required()
                                ->default(now())
                                ->label(__('accounting::lc.issue_date')),

                            DatePicker::make('expiry_date')
                                ->required()
                                ->default(now()->addMonths(3))
                                ->label(__('accounting::lc.expiry_date')),

                            DatePicker::make('shipment_date')
                                ->label(__('accounting::lc.latest_shipment_date')),

                            TextInput::make('incoterm')
                                ->label(__('accounting::lc.incoterm'))
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
                    ->label(__('accounting::lc.lc_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('accounting::lc.beneficiary'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('accounting::lc.amount'))
                    ->money(fn (LetterOfCredit $record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label(__('accounting::lc.balance'))
                    ->money(fn (LetterOfCredit $record) => $record->currency->code)
                    ->color(fn (LetterOfCredit $record) => $record->balance->isZero() ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label(__('accounting::lc.issue_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('accounting::lc.expiry_date'))
                    ->date()
                    ->sortable()
                    ->color(fn (LetterOfCredit $record) => $record->expiry_date->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('accounting::lc.type'))
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('accounting::lc.status'))
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
                    ->label(__('accounting::lc.issue'))
                    ->icon('heroicon-m-document-check')
                    ->color('info')
                    ->form([
                        TextInput::make('bank_reference')
                            ->label(__('accounting::lc.bank_reference_number'))
                            ->required(),
                        DatePicker::make('issue_date')
                            ->label(__('accounting::lc.issue_date'))
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
                    ->label(__('accounting::lc.cancel'))
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
