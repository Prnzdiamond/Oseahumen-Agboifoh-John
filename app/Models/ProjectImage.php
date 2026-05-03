<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectImage extends Model
{
    protected $fillable = [
        'project_id',
        'image',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($projectImage) {
            if ($projectImage->isDirty('image') && $projectImage->getOriginal('image')) {
                Storage::disk('public')->delete($projectImage->getOriginal('image'));
            }
        });

        static::deleting(function ($projectImage) {
            if ($projectImage->image) {
                Storage::disk('public')->delete($projectImage->image);
            }
        });
    }
}
