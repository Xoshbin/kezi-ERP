<?php

namespace App\Filament\Resources\BankStatementResource\Pages;

use App\Filament\Resources\BankStatementResource;
use Filament\Resources\Pages\Page;
use App\Models\BankStatement;

class BankReconciliation extends Page
{
    protected static string $resource = BankStatementResource::class;

    protected static string $view = 'filament.resources.bank-statement-resource.pages.bank-reconciliation';

    // This public property's name ('record') matches the route parameter.
    // Filament will automatically find the BankStatement and inject it here.
    public BankStatement $record;

    // The mount() method has been removed to allow Filament's
    // automatic model binding to work without interference.
}
