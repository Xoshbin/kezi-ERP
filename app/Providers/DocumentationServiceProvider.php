<?php

namespace App\Providers;

use App\Services\DocumentationService;
use Illuminate\Support\ServiceProvider;

class DocumentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentationService::class, function () {
            return DocumentationService::make();
        });
    }
}

