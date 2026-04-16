<?php

namespace App\Jobs;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class WarmProjectCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Rebuild every project-related cache entry so the next visitor
     * never hits a cold cache regardless of which page they land on.
     *
     * Covers:
     *   projects.all               — raw Eloquent collection
     *   projects.transformed       — full API-ready list (resolves Cloudinary URLs)
     *   project.{slug}             — per-slug raw model
     *   projects.transformed.{slug}— per-slug API-ready object
     *   projects.meta              — distinct industries, types, tags
     */
    public function handle(): void
    {
        // ── 1. Raw collection ────────────────────────────────────────────────
        $projects = Project::with([
            'images' => fn ($q) => $q->select('id', 'project_id', 'image'),
        ])->select([
            'id', 'title', 'slug', 'description', 'demo_url',
            'technologies', 'key_features', 'status', 'type',
            'industry', 'tags', 'source_code', 'completion_date',
            'duration', 'is_featured', 'cover_image', 'image',
            'created_at', 'updated_at',
        ])->latest()->get();

        Cache::forever('projects.all', $projects);

        // ── 2. Transformed list (resolves all Cloudinary URLs at once) ───────
        $transformed = ProjectResource::collection($projects)->resolve();
        Cache::forever('projects.transformed', $transformed);

        // ── 3. Per-slug caches ───────────────────────────────────────────────
        foreach ($projects as $project) {
            Cache::forever("project.{$project->slug}", $project);
            Cache::forever(
                "projects.transformed.{$project->slug}",
                (new ProjectResource($project))->resolve()
            );
        }

        // ── 4. Meta: distinct filter dimensions ─────────────────────────────
        $industries = $projects->pluck('industry')->filter()->unique()->sort()->values();
        $types      = $projects->pluck('type')->filter()->unique()->sort()->values();
        $tags       = $projects->pluck('tags')
            ->filter()
            ->flatMap(fn ($t) => is_array($t) ? $t : [])
            ->unique()->sort()->values();

        Cache::forever('projects.meta', [
            'industries' => $industries,
            'types'      => $types,
            'tags'       => $tags,
        ]);
    }
}
