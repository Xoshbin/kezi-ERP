<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource;

class EditLetterOfCredit extends EditRecord
{
    protected static string $resource = LetterOfCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
