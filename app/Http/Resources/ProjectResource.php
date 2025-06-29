<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'demo_url' => $this->demo_url,
            'technologies' => $this->technologies,
            'key_features' => $this->key_features_list ?? [],
            'status' => $this->status,
            'type' => $this->type,
            'source_code' => $this->source_code_list ?? [],
            'completion_date' => $this->completion_date?->format('Y-m-d'),
            'duration' => $this->duration,
            'formatted_duration' => $this->getFormattedDuration(),
            'is_featured' => $this->is_featured,
            'cover_image' => $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null,
            'main_image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'images' => $this->images
                ->pluck('image')
                ->map(fn($path) => Storage::disk('public')->url($path)),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Helper data for UI
            'status_color' => $this->getStatusColor(),
            'type_color' => $this->getTypeColor(),
            'has_public_source' => $this->hasPublicSourceCode(),
            'primary_source_url' => $this->getPrimarySourceCodeUrl(),
        ];
    }
}
