<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
                $oldImage = $projectImage->getOriginal('image');
                if ($oldImage) {
                    \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($oldImage);
                }
            }
        });

        static::deleting(function ($projectImage) {
            if ($projectImage->image) {
                \Illuminate\Support\Facades\Storage::disk('cloudinary')->delete($projectImage->image);
            }
        });
    }
}
