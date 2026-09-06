<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $staffRole = Role::where('name', 'staff')->first();

        if ($staffRole) {
            $this->record->roles()->attach($staffRole);
        }
    }
}
