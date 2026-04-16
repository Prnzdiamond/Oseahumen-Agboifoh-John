<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * A visual icon picker backed by the icons table (synced from Lucide).
 *
 * Usage:
 *   IconPicker::make('icon')->label('Choose Icon')
 *
 * The admin types a keyword (e.g. "game", "music", "mail") and sees a
 * scrollable grid of matching icons with live SVG previews.
 * Clicking one saves the exact Lucide icon name (e.g. "gamepad-2") to the DB.
 * The frontend renders it with <component :is="LucideIcons[iconName]" />.
 */
class IconPicker extends Field
{
    protected string $view = 'filament.forms.components.icon-picker';
}
