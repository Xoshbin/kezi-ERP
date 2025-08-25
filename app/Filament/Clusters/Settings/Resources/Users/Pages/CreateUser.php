<?php

namespace App\Filament\Clusters\Settings\Resources\Users\Pages;

use App\Filament\Clusters\Settings\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return __('user.page.create.title');
    }
}
