<?php

// FILE 3: app/Filament/Resources/TechnologyResource/Pages/EditTechnology.php
namespace App\Filament\Resources\TechnologyResource\Pages;
use App\Filament\Resources\TechnologyResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
class EditTechnology extends EditRecord
{
    protected static string $resource = TechnologyResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}