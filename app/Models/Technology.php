<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class Technology extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'devicon_name',
        'devicon_version',
        'devicon_colored',
        'color',
        'category',
        'aliases',
        'custom_icon_url',
        'is_active',
        'is_manual',
    ];

    protected $casts = [
        'aliases'         => 'array',
        'is_active'       => 'boolean',
        'is_manual'       => 'boolean',
        'devicon_colored' => 'boolean',
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

    // ── Icon resolution ───────────────────────────────────────────────────────

    /**
     * Returns the full devicon CSS class string ready to use in a template.
     * e.g. "devicon-laravel-plain colored"
     * Returns null if this technology has no devicon entry.
     */
    public function getDeviconClass(): ?string
    {
        if (!$this->devicon_name) {
            return null;
        }

        $class = "devicon-{$this->devicon_name}-{$this->devicon_version}";

        if ($this->devicon_colored) {
            $class .= ' colored';
        }

        return $class;
    }

    // ── Name normalization ────────────────────────────────────────────────────

    /**
     * Normalize any tech name string to a comparable form for fuzzy matching.
     *
     * "Vue.js"  → "vuejs"
     * "Node.js" → "nodejs"
     * "C++"     → "c++"
     * "Tailwind CSS" → "tailwindcss"
     */
    public static function normalize(string $name): string
    {
        return strtolower(preg_replace('/[\s\.\-_]/', '', $name));
    }

    /**
     * Find a Technology record by any of its known names or aliases.
     * Case-insensitive and spacing-insensitive.
     *
     * Returns null if not in the catalog — callers handle the fallback display.
     */
    public static function findByName(string $name): ?self
    {
        $normalized = self::normalize($name);

        // 1. Exact slug match (fastest — uses index)
        $tech = self::active()->where('slug', $normalized)->first();
        if ($tech) {
            return $tech;
        }

        // 2. Normalized name match
        $tech = self::active()->get()->first(
            fn ($t) => self::normalize($t->name) === $normalized
        );
        if ($tech) {
            return $tech;
        }

        // 3. Alias array match
        return self::active()->get()->first(function ($t) use ($normalized) {
            foreach ($t->aliases ?? [] as $alias) {
                if (self::normalize($alias) === $normalized) {
                    return true;
                }
            }
            return false;
        });
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tech) {
            if (empty($tech->slug)) {
                // Strip dots, plusses, hashes before slugging
                // "Vue.js" → "vuejs", "C++" → "c", "C#" → "c"
                $tech->slug = Str::slug(
                    preg_replace('/[\.\+#]/', '', $tech->name)
                );
            }
        });

        // Bust the catalog cache whenever any record changes so the
        // frontend always gets fresh data on next request
        static::saved(function () {
            Cache::forget('technologies.catalog');
        });

        static::deleted(function () {
            Cache::forget('technologies.catalog');
        });
    }
}
