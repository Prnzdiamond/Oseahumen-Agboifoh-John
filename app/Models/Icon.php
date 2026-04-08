<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Icon extends Model
{
    protected $fillable = [
        'name',
        'keywords',
        'category',
        'svg',
        'is_active',
        'is_manual',
    ];

    protected $casts = [
        'keywords'  => 'array',
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Search icons by keyword — used by the visual picker in Filament.
     * Searches both the name and the keywords array.
     *
     * "game"  → matches gamepad-2, dice-5, joystick (all have "game" in keywords)
     * "music" → matches music, music-2, headphones, mic etc.
     * "mail"  → matches mail, mail-open, mail-check etc.
     */
    public function scopeSearch($query, string $term)
    {
        $term = strtolower(trim($term));

        return $query->where('is_active', true)->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
              ->orWhereRaw("keywords::text ilike ?", ["%{$term}%"]);
        });
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('icons.catalog');
        });

        static::deleted(function () {
            Cache::forget('icons.catalog');
        });
    }
}
