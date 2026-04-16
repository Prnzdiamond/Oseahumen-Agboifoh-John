<?php
// ─────────────────────────────────────────────────────────────────────────────
// FILE 2: app/Filament/Resources/TechnologyResource/Pages/CreateTechnology.php
namespace App\Filament\Resources\TechnologyResource\Pages;
use App\Filament\Resources\TechnologyResource;
use Filament\Resources\Pages\CreateRecord;
class CreateTechnology extends CreateRecord
{
    protected static string $resource = TechnologyResource::class;
}