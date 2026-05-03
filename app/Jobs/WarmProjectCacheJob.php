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
 * Fired from: Project::boot() → saved() / deleted() observers.
 * Runs on: the database queue (QUEUE_CONNECTION=database).
 *
 * What it warms:
 *   projects.all           — raw Eloquent collection
 *   projects.transformed   — API-ready array (the one /api/projects returns)
 *   project.{slug}         — raw model per project
 *   projects.transformed.{slug} — API-ready single project (what /api/projects/{slug} returns)
 *   projects.meta          — distinct industries, types, tags
 *
 * Because the job runs in the background, the main request returns immediately
 * after the DB write. The next visitor after a cache bust will still get a
 * warm cache within seconds rather than waiting for a cold build.
 *
 * If the job itself fails (e.g. Cloudinary is momentarily unreachable),
 * it retries up to 3 times with a 30-second backoff. In the worst case,
 * the cache remains cold and the next visitor warms it on-demand — same
 * behaviour as before, no regression.
 */
class WarmProjectCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30; // seconds between retries

    public function handle(): void
    {
        Log::info('[WarmProjectCacheJob] Starting cache warm...');

        // ── Step 1: Rebuild the list of all projects ──────────────────────────
        $projects = Project::with([
            'images' => fn ($q) => $q->select('id', 'project_id', 'image'),
        ])
            ->select([
                'id', 'title', 'slug', 'description', 'demo_url',
                'technologies', 'key_features', 'status', 'type', 'source_code',
                'completion_date', 'duration', 'is_featured',
                'cover_image', 'image', 'industry', 'tags',
                'created_at', 'updated_at',
            ])
            ->latest()
            ->get();

        Cache::forever('projects.all', $projects);

        // ── Step 2: Build the transformed list (Cloudinary URLs resolved) ─────
        $transformed = ProjectResource::collection($projects)->resolve();
        Cache::forever('projects.transformed', $transformed);

        // ── Step 3: Warm each individual project cache ────────────────────────
        // This is what makes /api/projects/{slug} instant even on a cold page
        // load — the detail cache is pre-populated before anyone visits.
        foreach ($projects as $project) {
            Cache::forever("project.{$project->slug}", $project);

            $single = (new ProjectResource($project))->resolve();
            Cache::forever("projects.transformed.{$project->slug}", $single);
        }

        // ── Step 4: Rebuild the meta cache ────────────────────────────────────
        $this->warmMetaCache($projects);

        Log::info('[WarmProjectCacheJob] Done. Warmed ' . $projects->count() . ' projects.');
    }

    /**
     * Derives and caches the distinct filter dimensions from all projects.
     * This is what /api/projects/meta returns.
     */
    private function warmMetaCache($projects): void
    {
        $industries = $projects
            ->pluck('industry')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $types = $projects
            ->pluck('type')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Tags is a JSON array per project — flatten and deduplicate
        $tags = $projects
            ->flatMap(fn ($p) => $p->tags ?? [])
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        Cache::forever('projects.meta', compact('industries', 'types', 'tags'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[WarmProjectCacheJob] Failed: ' . $exception->getMessage());
    }
}
