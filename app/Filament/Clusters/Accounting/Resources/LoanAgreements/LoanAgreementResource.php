<?php

namespace App\Filament\Clusters\Accounting\Resources\LoanAgreements;

use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\CreateLoanAgreement;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\EditLoanAgreement;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ListLoanAgreements;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Pages\ViewLoanAgreement;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas\LoanAgreementForm;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas\LoanAgreementInfolist;
use App\Filament\Clusters\Accounting\Resources\LoanAgreements\Tables\LoanAgreementsTable;
use App\Models\LoanAgreement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoanAgreementResource extends Resource
{
    protected static ?string $model = LoanAgreement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = AccountingCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

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
            //
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
}
