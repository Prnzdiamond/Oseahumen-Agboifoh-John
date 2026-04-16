<?php

// FILE 1: app/Filament/Resources/TechnologyResource/Pages/ListTechnologies.php
namespace App\Filament\Resources\TechnologyResource\Pages;
use App\Filament\Resources\TechnologyResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
class ListTechnologies extends ListRecords
{
    protected static string $resource = TechnologyResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}