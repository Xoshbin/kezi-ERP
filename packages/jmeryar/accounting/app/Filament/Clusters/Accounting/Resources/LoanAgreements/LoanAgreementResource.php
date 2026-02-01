<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Jmeryar\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\EditLoanAgreement;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ListLoanAgreements;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers\FeeLinesRelationManager;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers\RateChangesRelationManager;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers\ScheduleEntriesRelationManager;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas\LoanAgreementForm;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas\LoanAgreementInfolist;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Tables\LoanAgreementsTable;
use Jmeryar\Accounting\Models\LoanAgreement;

class LoanAgreementResource extends Resource
{
    protected static ?string $model = LoanAgreement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = AccountingCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

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
