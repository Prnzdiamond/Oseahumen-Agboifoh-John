<?php

namespace App\Filament\Resources\OwnerProfileResource\Pages;

use App\Filament\Resources\OwnerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwnerProfiles extends ListRecords
{
    protected static string $resource = OwnerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
