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
use Illuminate\Support\Facades\Log;

/**
 * Warms the full project cache after any save or delete.
 *
 * With local storage, URL generation is instant (no external API calls),
 * so this job is now purely a cache-rebuild — no network dependency at all.
 */
class WarmProjectCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function handle(): void
    {
        Log::info('[WarmProjectCacheJob] Starting cache warm...');

        $projects = Project::with([
            'images' => fn($q) => $q->select('id', 'project_id', 'image'),
        ])
            ->select([
                'id',
                'title',
                'slug',
                'description',
                'demo_url',
                'technologies',
                'key_features',
                'status',
                'type',
                'source_code',
                'completion_date',
                'duration',
                'is_featured',
                'cover_image',
                'image',
                'industry',
                'tags',
                'created_at',
                'updated_at',
            ])
            ->latest()
            ->get();

        Cache::forever('projects.all', $projects);

        $transformed = ProjectResource::collection($projects)->resolve();
        Cache::forever('projects.transformed', $transformed);

        foreach ($projects as $project) {
            Cache::forever("project.{$project->slug}", $project);

            $single = (new ProjectResource($project))->resolve();
            Cache::forever("projects.transformed.{$project->slug}", $single);
        }

        $this->warmMetaCache($projects);

        Log::info('[WarmProjectCacheJob] Done. Warmed ' . $projects->count() . ' projects.');
    }

    private function warmMetaCache($projects): void
    {
        $industries = $projects->pluck('industry')->filter()->unique()->sort()->values()->toArray();
        $types = $projects->pluck('type')->filter()->unique()->sort()->values()->toArray();
        $tags = $projects->flatMap(fn($p) => $p->tags ?? [])->filter()->unique()->sort()->values()->toArray();

        Cache::forever('projects.meta', compact('industries', 'types', 'tags'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[WarmProjectCacheJob] Failed: ' . $exception->getMessage());
    }
}
