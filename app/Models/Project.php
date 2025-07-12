<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
    ];

    protected $casts = [
        'technologies' => 'array',
        'key_features' => 'array',
        'source_code' => 'array',
        'completion_date' => 'date',
        'is_featured' => 'boolean',
    ];

    // Optimized relationship with selective loading
    public function images()
    {
        return $this->hasMany(ProjectImage::class)->select('id', 'project_id', 'image');
    }

    // Cached accessor for key features
    protected function keyFeaturesList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->key_features) {
                    return [];
                }

                // Use array_map for better performance than collect()
                return array_map(function ($item) {
                    if (is_array($item)) {
                        return [
                            'title' => $item['title'] ?? $item['name'] ?? '',
                            'description' => $item['description'] ?? '',
                            'icon' => $item['icon'] ?? null,
                        ];
                    }
                    return [
                        'title' => $item,
                        'description' => '',
                        'icon' => null,
                    ];
                }, $this->key_features);
            }
        );
    }

    // Cached accessor for source code
    protected function sourceCodeList(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$this->source_code) {
                    return [];
                }

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

    // Optimized helper methods
    public function hasPublicSourceCode(): bool
    {
        if (!$this->source_code) {
            return false;
        }

        foreach ($this->source_code as $item) {
            if (($item['is_public'] ?? true) === true) {
                return true;
            }
        }
        return false;
    }

    public function getPrimarySourceCodeUrl(): ?string
    {
        if (!$this->source_code) {
            return null;
        }

        foreach ($this->source_code as $item) {
            if (($item['is_public'] ?? true) === true) {
                return $item['url'] ?? null;
            }
        }
        return null;
    }

    // Optimized duration formatting
    public function getFormattedDuration(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        if ($this->duration < 30) {
            return $this->duration . ' days';
        } elseif ($this->duration < 365) {
            $months = round($this->duration / 30);
            return $months . ' month' . ($months > 1 ? 's' : '');
        } else {
            $years = round($this->duration / 365, 1);
            return $years . ' year' . ($years > 1 ? 's' : '');
        }
    }

    // Static color mappings for better performance
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

    // Scopes with indexes for better query performance
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

            // Add this section for Cloudinary file deletion
            if ($project->isDirty('image') && $project->getOriginal('image')) {
                $oldImage = $project->getOriginal('image');
                if ($oldImage) {
                    \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($oldImage);
                }
            }

            if ($project->isDirty('cover_image') && $project->getOriginal('cover_image')) {
                $oldCoverImage = $project->getOriginal('cover_image');
                if ($oldCoverImage) {
                    \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($oldCoverImage);
                }
            }
        });

        // Add this new static::deleting method
        static::deleting(function ($project) {
            if ($project->image) {
                \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($project->image);
            }
            if ($project->cover_image) {
                \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($project->cover_image);
            }
        });

        // Clear cache when project is updated (existing code)
        static::saved(function ($project) {
            \Illuminate\Support\Facades\Cache::forget('projects.all');
            \Illuminate\Support\Facades\Cache::forget("project.{$project->slug}");
            \Illuminate\Support\Facades\Cache::forget("project.transformed.{$project->slug}");
        });

        static::deleted(function ($project) {
            \Illuminate\Support\Facades\Cache::forget('projects.all');
            \Illuminate\Support\Facades\Cache::forget("project.{$project->slug}");
            \Illuminate\Support\Facades\Cache::forget("project.transformed");
            \Illuminate\Support\Facades\Cache::forget("project.transformed.{$project->slug}");
        });
    }
}
