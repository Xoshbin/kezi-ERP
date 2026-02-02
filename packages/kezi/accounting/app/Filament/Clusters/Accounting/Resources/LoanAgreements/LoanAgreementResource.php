<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\EditLoanAgreement;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ListLoanAgreements;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers\FeeLinesRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers\RateChangesRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers\ScheduleEntriesRelationManager;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas\LoanAgreementForm;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas\LoanAgreementInfolist;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Tables\LoanAgreementsTable;
use Kezi\Accounting\Models\LoanAgreement;

class LoanAgreementResource extends Resource
{
    protected static ?string $model = LoanAgreement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = AccountingCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return __('Accounting');
    }

    public static function getModelLabel(): string
    {
        return __('accounting::loan.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accounting::loan.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::loan.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return LoanAgreementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoanAgreementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanAgreementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FeeLinesRelationManager::class,
            RateChangesRelationManager::class,
            ScheduleEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoanAgreements::route('/'),
            'create' => CreateLoanAgreement::route('/create'),
            'view' => ViewLoanAgreement::route('/{record}'),
            'edit' => EditLoanAgreement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', \Filament\Facades\Filament::getTenant()?->id);
    }
}
