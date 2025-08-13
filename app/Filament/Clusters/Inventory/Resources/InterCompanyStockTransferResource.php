<?php

namespace App\Filament\Clusters\Inventory\Resources;

use App\Actions\Inventory\CreateInterCompanyStockTransferAction;
use App\DataTransferObjects\Inventory\CreateInterCompanyTransferDTO;
use App\Filament\Clusters\Inventory;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\StockMove;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\Inventory\Resources\InterCompanyStockTransferResource\Pages;

class InterCompanyStockTransferResource extends Resource
{
    protected static ?string $model = StockMove::class;

    protected static ?string $cluster = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'inter-company-stock-transfers';

    public static function getModelLabel(): string
    {
        return 'Inter-Company Stock Transfer';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Inter-Company Stock Transfers';
    }

    public static function getNavigationLabel(): string
    {
        return 'Inter-Company Transfers';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('Transfer Details'))
                ->description(__('Create a stock transfer between companies'))
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('source_company_id')
                            ->label(__('Source Company'))
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set) => $set('target_company_id', null)),
                        Forms\Components\Select::make('target_company_id')
                            ->label(__('Target Company'))
                            ->options(function (callable $get) {
                                $sourceCompanyId = $get('source_company_id');
                                if (!$sourceCompanyId) {
                                    return [];
                                }

                                // Get companies that have partner relationships with the source company
                                return Partner::where('company_id', $sourceCompanyId)
                                    ->whereNotNull('linked_company_id')
                                    ->with('linkedCompany')
                                    ->get()
                                    ->pluck('linkedCompany.name', 'linked_company_id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn (callable $get) => !$get('source_company_id')),
                    ]),

                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('product_id')
                            ->label(__('Product'))
                            ->options(function (callable $get) {
                                $companyId = $get('source_company_id');
                                if (!$companyId) {
                                    return [];
                                }

                                return Product::where('company_id', $companyId)
                                    ->where('type', \App\Enums\Products\ProductType::Storable)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->disabled(fn (callable $get) => !$get('source_company_id')),
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->required()
                            ->numeric()
                            ->minValue(0.0001)
                            ->step(0.0001),
                        Forms\Components\DatePicker::make('transfer_date')
                            ->label(__('Transfer Date'))
                            ->required()
                            ->default(now()),
                    ]),

                    Forms\Components\TextInput::make('reference')
                        ->label(__('Reference'))
                        ->maxLength(255)
                        ->placeholder(__('Optional reference for this transfer')),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('Notes'))
                        ->maxLength(1000)
                        ->placeholder(__('Optional notes about this transfer')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show inter-company transfers (those with IC-TRANSFER reference)
                return $query->where('reference', 'like', 'IC-TRANSFER-%');
            })
            ->columns([
                Tables\Columns\TextColumn::make('move_date')
                    ->label(__('Transfer Date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('Quantity'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('move_type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->color(fn ($state): string => match ($state) {
                        \App\Enums\Inventory\StockMoveType::Incoming => 'success',
                        \App\Enums\Inventory\StockMoveType::Outgoing => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('fromLocation.name')
                    ->label(__('From'))
                    ->limit(20),
                Tables\Columns\TextColumn::make('toLocation.name')
                    ->label(__('To'))
                    ->limit(20),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->color(fn ($state): string => match ($state) {
                        \App\Enums\Inventory\StockMoveStatus::Done => 'success',
                        \App\Enums\Inventory\StockMoveStatus::Confirmed => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('move_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label(__('Company')),
                Tables\Filters\SelectFilter::make('move_type')
                    ->options([
                        \App\Enums\Inventory\StockMoveType::Incoming->value => 'Incoming',
                        \App\Enums\Inventory\StockMoveType::Outgoing->value => 'Outgoing',
                    ])
                    ->label(__('Transfer Type')),
                Tables\Filters\Filter::make('transfer_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('From Date')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('Until Date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('move_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('move_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Create Transfer'))
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by_user_id'] = Filament::auth()->id();
                        return $data;
                    })
                    ->using(function (array $data): StockMove {
                        $dto = new CreateInterCompanyTransferDTO(
                            source_company_id: $data['source_company_id'],
                            target_company_id: $data['target_company_id'],
                            product_id: $data['product_id'],
                            quantity: $data['quantity'],
                            transfer_date: Carbon::parse($data['transfer_date']),
                            created_by_user_id: $data['created_by_user_id'],
                            reference: $data['reference'] ?? null,
                            notes: $data['notes'] ?? null,
                        );

                        $result = app(CreateInterCompanyStockTransferAction::class)->createBidirectionalTransfer($dto);

                        Notification::make()
                            ->title('Inter-company transfer created successfully')
                            ->body("Created delivery move #{$result['delivery']->id} and receipt move #{$result['receipt']->id}")
                            ->success()
                            ->send();

                        return $result['delivery']; // Return the delivery move for the redirect
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterCompanyStockTransfers::route('/'),
            'view' => Pages\ViewInterCompanyStockTransfer::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('reference', 'like', 'IC-TRANSFER-%')
            ->with(['company', 'product', 'fromLocation', 'toLocation']);
    }
}
