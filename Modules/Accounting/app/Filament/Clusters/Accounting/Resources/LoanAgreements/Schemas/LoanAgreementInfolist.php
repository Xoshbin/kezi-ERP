<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LoanAgreementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name'),
                TextEntry::make('partner.name'),
                TextEntry::make('name'),
                TextEntry::make('loan_date')
                    ->date(),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('maturity_date')
                    ->date(),
                TextEntry::make('duration_months')
                    ->numeric(),
                TextEntry::make('currency.name'),
                TextEntry::make('principal_amount')
                    ->numeric(),
                TextEntry::make('outstanding_principal')
                    ->numeric(),
                TextEntry::make('loan_type'),
                TextEntry::make('status'),
                TextEntry::make('schedule_method'),
                TextEntry::make('interest_rate')
                    ->numeric(),
                IconEntry::make('eir_enabled')
                    ->boolean(),
                TextEntry::make('eir_rate')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
