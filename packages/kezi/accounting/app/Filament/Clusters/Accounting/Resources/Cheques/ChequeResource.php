<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques;

use Brick\Money\Money;
use \Filament\Actions\Action;
use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages;
use Kezi\Payment\DataTransferObjects\Cheques\BounceChequeDTO;
use Kezi\Payment\DataTransferObjects\Cheques\ClearChequeDTO;
use Kezi\Payment\DataTransferObjects\Cheques\DepositChequeDTO;
use Kezi\Payment\Enums\Cheques\ChequeStatus;
use Kezi\Payment\Enums\Cheques\ChequeType;
use Kezi\Payment\Models\Cheque;
use Kezi\Payment\Services\Cheques\ChequeService;

class ChequeResource extends Resource
{
    protected static ?string $model = Cheque::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getModelLabel(): string
    {
        return __('accounting::cheque.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::cheque.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.banking_cash');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::cheque.details'))
                    ->schema([
                        \Filament\Forms\Components\Hidden::make('company_id')
                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                        Group::make([

                            // Type (Payable/Receivable)
                            ToggleButtons::make('type')
                                ->options(ChequeType::class)
                                ->inline()
                                ->default(ChequeType::Payable->value)
                                ->required()
                                ->live() // Reactive to show/hide fields
                                ->disabled(fn (?Cheque $record) => $record && $record->exists), // Cannot change type after creation

                            // Payee / Drawer
                            Select::make('partner_id')
                                ->relationship('partner', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label(fn (Get $get) => $get('type') === ChequeType::Payable->value ? __('accounting::cheque.payee') : __('accounting::cheque.drawer')),

                        ])->columnSpanFull(),

                        Group::make([

                            // Amount
                            TextInput::make('amount')
                                ->label(__('accounting::cheque.amount'))
                                ->required()
                                ->numeric()
                                ->prefix('IQD'),

                            // Currency (for now select from currencies table)
                            Select::make('currency_id')
                                ->relationship('currency', 'code')
                                ->required()
                                ->default(1), // Default to IQD or base

                            // Cheque Number
                            TextInput::make('cheque_number')
                                ->required()
                                ->maxLength(50)
                                ->label(__('accounting::cheque.cheque_number')),

                            // Dates
                            DatePicker::make('issue_date')
                                ->required()
                                ->default(now())
                                ->label(__('accounting::cheque.issue_date')),

                            DatePicker::make('due_date')
                                ->required()
                                ->default(now()->addDays(30))
                                ->label(__('accounting::cheque.due_date')),

                        ])->columns(2),

                        // Section for Payable-specific logic (Chequebook)
                        Group::make([
                            Select::make('chequebook_id')
                                ->relationship('chequebook', 'name', fn ($query) => $query->where('is_active', true))
                                ->label(__('accounting::cheque.from_cheque_book'))
                                ->searchable()
                                ->visible(fn (Get $get) => $get('type') === ChequeType::Payable->value),

                            Select::make('journal_id')
                                ->relationship('journal', 'name')
                                ->required()
                                ->label(__('accounting::cheque.bank_account'))
                                ->helperText('The bank account associated with this transaction.'),
                        ])->columns(2),

                        // Section for Receivable-specific logic (Bank Name)
                        Group::make([
                            TextInput::make('bank_name')
                                ->label(__('accounting::cheque.drawer_bank'))
                                ->visible(fn (Get $get) => $get('type') === ChequeType::Receivable->value),
                        ]),

                        Textarea::make('memo')
                            ->label(__('accounting::cheque.memo'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cheque_number')
                    ->label(__('accounting::cheque.cheque_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label(__('accounting::cheque.issue_date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('accounting::cheque.due_date'))
                    ->date()
                    ->sortable()
                    ->color(fn (Cheque $record) => $record->due_date->isPast() && $record->status === ChequeStatus::Draft ? 'danger' : null),

                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('accounting::cheque.party'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('accounting::cheque.amount'))
                    ->money(fn (Cheque $record) => $record->currency->code) // Assuming relationship loaded
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('accounting::cheque.type'))
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('accounting::cheque.status'))
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ChequeStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->options(ChequeType::class),
            ])
            ->actions([
                EditAction::make(),

                // Hand Over (Payable)
                Action::make('hand_over')
                    ->label(__('accounting::cheque.hand_over'))
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Cheque $record) => $record->type === ChequeType::Payable && in_array($record->status, [ChequeStatus::Draft, ChequeStatus::Printed]))
                    ->action(function (Cheque $record) {
                        app(ChequeService::class)->handOver($record, auth()->user());
                    }),

                // Deposit (Receivable)
                Action::make('deposit')
                    ->label(__('accounting::cheque.deposit'))
                    ->icon('heroicon-m-building-library')
                    ->color('info')
                    ->form([
                        DatePicker::make('deposited_at')
                            ->label(__('accounting::cheque.deposit_date'))
                            ->required()
                            ->default(now()),
                    ])
                    ->visible(fn (Cheque $record) => $record->type === ChequeType::Receivable && $record->status === ChequeStatus::Draft)
                    ->action(function (Cheque $record, array $data) {
                        $dto = new DepositChequeDTO(
                            cheque_id: $record->id,
                            deposited_at: $data['deposited_at']
                        );
                        app(ChequeService::class)->deposit($record, $dto, auth()->user());
                    }),

                // Clear
                Action::make('clear')
                    ->label(__('accounting::cheque.clear'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->form([
                        DatePicker::make('cleared_at')
                            ->label(__('accounting::cheque.cleared_date'))
                            ->required()
                            ->default(now()),
                    ])
                    ->visible(fn (Cheque $record) => in_array($record->status, [ChequeStatus::HandedOver, ChequeStatus::Deposited]))
                    ->action(function (Cheque $record, array $data) {
                        $dto = new ClearChequeDTO(
                            cheque_id: $record->id,
                            cleared_at: $data['cleared_at']
                        );
                        app(ChequeService::class)->clear($record, $dto, auth()->user());
                    }),

                // Bounce
                Action::make('bounce')
                    ->label(__('accounting::cheque.bounce'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->form([
                        DatePicker::make('bounced_at')
                            ->label(__('accounting::cheque.bounced_date'))
                            ->required()
                            ->default(now()),
                        Textarea::make('reason')
                            ->label(__('accounting::cheque.reason'))
                            ->required(),
                        TextInput::make('bank_charges')
                            ->label(__('accounting::cheque.bank_charges'))
                            ->numeric()
                            ->prefix('IQD'),
                        Textarea::make('notes')
                            ->label(__('accounting::cheque.notes')),
                    ])
                    ->visible(fn (Cheque $record) => in_array($record->status, [ChequeStatus::HandedOver, ChequeStatus::Deposited]))
                    ->action(function (Cheque $record, array $data) {
                        $charges = $data['bank_charges'] ? Money::of($data['bank_charges'], $record->currency->code) : null;
                        $dto = new BounceChequeDTO(
                            cheque_id: $record->id,
                            bounced_at: $data['bounced_at'],
                            reason: $data['reason'],
                            bank_charges: $charges,
                            notes: $data['notes']
                        );
                        app(ChequeService::class)->bounce($record, $dto, auth()->user());
                    }),

                // Cancel (Draft only)
                Action::make('cancel')
                    ->label(__('accounting::cheque.cancel'))
                    ->icon('heroicon-m-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Cheque $record) => $record->status === ChequeStatus::Draft)
                    ->action(function (Cheque $record) {
                        app(ChequeService::class)->cancel($record, auth()->user());
                    }),

                // Print Cheque
                Action::make('print')
                    ->label(__('accounting::cheque.print'))
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->url(fn (Cheque $record) => route('cheques.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Cheque $record) => $record->type === ChequeType::Payable && in_array($record->status, [ChequeStatus::Draft, ChequeStatus::Printed])),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCheques::route('/'),
            'create' => Pages\CreateCheque::route('/create'),
            'edit' => Pages\EditCheque::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\UpcomingCheques::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}
