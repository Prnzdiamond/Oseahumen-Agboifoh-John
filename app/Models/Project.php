<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'title',
        'image',
        'cover_image',
        'description',
        'slug',
        'demo_url',
        'technologies',
        'key_features',
        'status',
        'type',
        'source_code',
        'completion_date',
        'duration',
        'is_featured',
        'industry',
        'tags',
    ];

    protected $casts = [
        'technologies' => 'array',
        'key_features' => 'array',
        'source_code' => 'array',
        'tags' => 'array',
        'completion_date' => 'date',
        'is_featured' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function images()
    {
        return $this->hasMany(ProjectImage::class)->select('id', 'project_id', 'image');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    protected function keyFeaturesList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->key_features)
                    return [];
                return array_map(function ($item) {
                    if (is_array($item)) {
                        return [
                            'title' => $item['title'] ?? $item['name'] ?? '',
                            'description' => $item['description'] ?? '',
                            'icon' => $item['icon'] ?? null,
                        ];
                    }
                    return ['title' => $item, 'description' => '', 'icon' => null];
                }, $this->key_features);
            }
        );
    }

    protected function sourceCodeList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->source_code)
                    return [];
                return array_map(function ($item) {
                    return [
                        'platform' => $item['platform'] ?? 'github',
                        'url' => $item['url'] ?? '',
                        'is_public' => $item['is_public'] ?? true,
                        'branch' => $item['branch'] ?? 'main',
                        'label' => $item['label'] ?? ucfirst($item['platform'] ?? 'Source'),
                    ];
                }, $this->source_code);
            }
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function hasPublicSourceCode(): bool
    {
        if (!$this->source_code)
            return false;
        foreach ($this->source_code as $item) {
            if (($item['is_public'] ?? true) === true)
                return true;
        }
        return false;
    }

    public function getPrimarySourceCodeUrl(): ?string
    {
        if (!$this->source_code)
            return null;
        foreach ($this->source_code as $item) {
            if (($item['is_public'] ?? true) === true)
                return $item['url'] ?? null;
        }
        return null;
    }

    public function getFormattedDuration(): ?string
    {
        if (!$this->duration)
            return null;
        if ($this->duration < 30)
            return $this->duration . ' days';
        if ($this->duration < 365) {
            $months = round($this->duration / 30);
            return $months . ' month' . ($months > 1 ? 's' : '');
        }
        $years = round($this->duration / 365, 1);
        return $years . ' year' . ($years > 1 ? 's' : '');
    }

    private static $statusColors = [
        'completed' => 'green',
        'in_progress' => 'blue',
        'planning' => 'yellow',
        'on_hold' => 'gray',
        'cancelled' => 'red',
    ];

    private static $typeColors = [
        'web_application' => 'blue',
        'mobile_app' => 'purple',
        'desktop_app' => 'indigo',
        'api' => 'green',
        'library' => 'yellow',
        'tool' => 'orange',
        'game' => 'pink',
        'other' => 'gray',
    ];

    public function getStatusColor(): string
    {
        return self::$statusColors[$this->status] ?? 'gray';
    }
    public function getTypeColor(): string
    {
        return self::$typeColors[$this->type] ?? 'gray';
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
    public function scopeByIndustry($query, $ind)
    {
        return $query->where('industry', $ind);
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            $project->slug = Str::slug($project->title);
        });

        static::updating(function ($project) {
            if ($project->isDirty('title')) {
                $project->slug = Str::slug($project->title);
            }
            // Delete old local files when replaced
            if ($project->isDirty('image') && $project->getOriginal('image')) {
                Storage::disk('public')->delete($project->getOriginal('image'));
            }
            if ($project->isDirty('cover_image') && $project->getOriginal('cover_image')) {
                Storage::disk('public')->delete($project->getOriginal('cover_image'));
            }
        });

        static::deleting(function ($project) {
            if ($project->image) {
                Storage::disk('public')->delete($project->image);
            }
            if ($project->cover_image) {
                Storage::disk('public')->delete($project->cover_image);
            }
        });

        static::saved(function ($project) {
            Cache::forget('projects.all');
            Cache::forget('projects.transformed');
            Cache::forget('projects.meta');
            Cache::forget("project.{$project->slug}");
            Cache::forget("projects.transformed.{$project->slug}");

            \App\Jobs\WarmProjectCacheJob::dispatch();
        });

        static::deleted(function ($project) {
            Cache::forget('projects.all');
            Cache::forget('projects.transformed');
            Cache::forget('projects.meta');
            Cache::forget("project.{$project->slug}");
            Cache::forget("projects.transformed.{$project->slug}");

            \App\Jobs\WarmProjectCacheJob::dispatch();
        });
    }
}
