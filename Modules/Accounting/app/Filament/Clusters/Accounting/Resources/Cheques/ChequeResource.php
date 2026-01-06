<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques;

use Brick\Money\Money;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages;
use Modules\Payment\DataTransferObjects\Cheques\BounceChequeDTO;
use Modules\Payment\DataTransferObjects\Cheques\ClearChequeDTO;
use Modules\Payment\DataTransferObjects\Cheques\DepositChequeDTO;
use Modules\Payment\Enums\Cheques\ChequeStatus;
use Modules\Payment\Enums\Cheques\ChequeType;
use Modules\Payment\Models\Cheque;
use Modules\Payment\Services\Cheques\ChequeService;

class ChequeResource extends Resource
{
    protected static ?string $model = Cheque::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Cheque Management';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cheque Details')
                    ->schema([
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
                                ->label(fn (Get $get) => $get('type') === ChequeType::Payable->value ? 'Payee' : 'Drawer (Customer)'),

                        ])->columnSpanFull(),

                        Group::make([

                            // Amount
                            TextInput::make('amount')
                                ->label('Amount')
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
                                ->label('Cheque Number'),

                            // Dates
                            DatePicker::make('issue_date')
                                ->required()
                                ->default(now())
                                ->label('Issue Date'),

                            DatePicker::make('due_date')
                                ->required()
                                ->default(now()->addDays(30))
                                ->label('Due Date (PDC)'),

                        ])->columns(2),

                        // Section for Payable-specific logic (Chequebook)
                        Group::make([
                            Select::make('chequebook_id')
                                ->relationship('chequebook', 'name', fn ($query) => $query->where('is_active', true))
                                ->label('From Cheque Book')
                                ->searchable()
                                ->visible(fn (Get $get) => $get('type') === ChequeType::Payable->value),

                            Select::make('journal_id')
                                ->relationship('journal', 'name')
                                ->required()
                                ->label('Bank Account')
                                ->helperText('The bank account associated with this transaction.'),
                        ])->columns(2),

                        // Section for Receivable-specific logic (Bank Name)
                        Group::make([
                            TextInput::make('bank_name')
                                ->label('Drawer Bank')
                                ->visible(fn (Get $get) => $get('type') === ChequeType::Receivable->value),
                        ]),

                        Textarea::make('memo')
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
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->color(fn (Cheque $record) => $record->due_date->isPast() && $record->status === ChequeStatus::Draft ? 'danger' : null),

                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Party')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (Cheque $record) => $record->currency->code) // Assuming relationship loaded
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
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
                    ->label('Hand Over')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Cheque $record) => $record->type === ChequeType::Payable && in_array($record->status, [ChequeStatus::Draft, ChequeStatus::Printed]))
                    ->action(function (Cheque $record) {
                        app(ChequeService::class)->handOver($record, auth()->user());
                    }),

                // Deposit (Receivable)
                Action::make('deposit')
                    ->label('Deposit')
                    ->icon('heroicon-m-building-library')
                    ->color('info')
                    ->form([
                        DatePicker::make('deposited_at')
                            ->label('Deposit Date')
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
                    ->label('Clear')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->form([
                        DatePicker::make('cleared_at')
                            ->label('Cleared Date')
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
                    ->label('Bounce')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->form([
                        DatePicker::make('bounced_at')
                            ->label('Bounced Date')
                            ->required()
                            ->default(now()),
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                        TextInput::make('bank_charges')
                            ->label('Bank Charges')
                            ->numeric()
                            ->prefix('IQD'),
                        Textarea::make('notes')
                            ->label('Notes'),
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
                    ->label('Cancel')
                    ->icon('heroicon-m-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Cheque $record) => $record->status === ChequeStatus::Draft)
                    ->action(function (Cheque $record) {
                        app(ChequeService::class)->cancel($record, auth()->user());
                    }),

                // Print Cheque
                Action::make('print')
                    ->label('Print')
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
}
