<?php

namespace App\Filament\Resources\AnalyticAccountResource\Pages;

use App\Filament\Resources\AnalyticAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnalyticAccount extends EditRecord
{
    protected static string $resource = AnalyticAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
