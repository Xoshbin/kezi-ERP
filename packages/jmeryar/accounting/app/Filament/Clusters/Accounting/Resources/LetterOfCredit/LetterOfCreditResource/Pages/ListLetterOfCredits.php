<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource;

class ListLetterOfCredits extends ListRecords
{
    protected static string $resource = LetterOfCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('understanding-letter-of-credit'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
