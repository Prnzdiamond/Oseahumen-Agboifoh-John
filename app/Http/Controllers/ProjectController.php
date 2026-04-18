<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProjectResource;

class ProjectController extends Controller
{
    /**
     * GET /api/projects
     *
     * Returns all projects. Uses a two-layer cache:
     *   projects.all         — raw Eloquent collection
     *   projects.transformed — API-serialised array
     *
     * Both caches are warmed in the background by WarmProjectCacheJob
     * immediately after any project save/delete, so cold-cache gaps are minimal.
     */
    public function index()
    {
        $projects = Cache::rememberForever('projects.all', function () {
            return Project::with([
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
        });

        if ($projects->isEmpty()) {
            return response()->json([
                'data'    => [],
                'message' => 'No projects found',
                'success' => true,
            ]);
        }

        $data = Cache::rememberForever('projects.transformed', function () use ($projects) {
            return ProjectResource::collection($projects)->resolve();
        });

        return response()->json([
            'data'    => $data,
            'message' => 'Projects retrieved successfully',
            'success' => true,
        ]);
    }

    /**
     * GET /api/projects/{slug}
     *
     * Returns a single project.
     *
     * OPTIMISATION: Instead of always building a fresh ProjectResource
     * (which re-calls Storage::disk('cloudinary')->url() for every image),
     * we first look the slug up in the already-warm `projects.transformed`
     * list cache. If found, return it directly — zero Cloudinary calls, instant.
     *
     * Only falls back to a full ProjectResource build when:
     *   a) The list cache hasn't been warmed yet (cold start)
     *   b) The project was somehow omitted from the list
     *
     * In practice, WarmProjectCacheJob pre-populates both
     * `projects.transformed.{slug}` AND `projects.transformed` within seconds
     * of every save, so both fast paths are always hot.
     */
    public function show(string $slug)
    {
        // ── Fast path 1: per-slug transformed cache ───────────────────────────
        // Warmed by WarmProjectCacheJob for each individual project.
        $cached = Cache::get("projects.transformed.{$slug}");
        if ($cached) {
            return response()->json([
                'data'    => $cached,
                'message' => 'Project retrieved successfully',
                'success' => true,
            ]);
        }

        // ── Fast path 2: look up inside the warm list cache ───────────────────
        // If the list is already warm, find this project's entry without
        // touching the database or Cloudinary at all.
        $listCache = Cache::get('projects.transformed');
        if ($listCache) {
            $entry = collect($listCache)->firstWhere('slug', $slug);
            if ($entry) {
                // Populate the per-slug cache so the next request hits fast path 1
                Cache::forever("projects.transformed.{$slug}", $entry);
                return response()->json([
                    'data'    => $entry,
                    'message' => 'Project retrieved successfully',
                    'success' => true,
                ]);
            }
        }

        // ── Cold path: build from scratch ─────────────────────────────────────
        // First visit after a cache bust, or on a completely cold server.
        // Populates both the raw model cache and the transformed cache so
        // subsequent requests hit fast path 1.
        $project = Cache::rememberForever("project.{$slug}", function () use ($slug) {
            return Project::with([
                'images' => fn ($q) => $q->select('id', 'project_id', 'image'),
            ])
                ->select([
                    'id', 'title', 'slug', 'description', 'demo_url',
                    'technologies', 'key_features', 'status', 'type', 'source_code',
                    'completion_date', 'duration', 'is_featured',
                    'cover_image', 'image', 'industry', 'tags',
                    'created_at', 'updated_at',
                ])
                ->where('slug', $slug)
                ->first();
        });

        if (!$project) {
            return response()->json([
                'message' => 'Project not found',
                'success' => false,
            ], 404);
        }

        $data = Cache::rememberForever("projects.transformed.{$slug}", function () use ($project) {
            return (new ProjectResource($project))->resolve();
        });

        return response()->json([
            'data'    => $data,
            'message' => 'Project retrieved successfully',
            'success' => true,
        ]);
    }

    /**
     * GET /api/projects/meta
     *
     * Returns the distinct filter dimensions across all active projects:
     *   - industries: ["e-commerce", "real-estate", "ai-ml", ...]
     *   - types:      ["web_application", "api", "mobile_app", ...]
     *   - tags:       ["live-chat", "websockets", "payment", ...]
     *
     * Cached forever. Busted by WarmProjectCacheJob on every project save/delete.
     * The frontend fetches this once to populate the filter panel axes.
     *
     * NOTE: This route must be declared BEFORE `projects/{slug}` in api.php
     * to prevent "meta" being treated as a slug. See routes/api.php.
     */
    public function meta()
    {
        $meta = Cache::rememberForever('projects.meta', function () {
            $projects = Cache::get('projects.all') ?? Project::select(
                'industry', 'type', 'tags'
            )->get();

            $industries = collect($projects)
                ->pluck('industry')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $types = collect($projects)
                ->pluck('type')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $tags = collect($projects)
                ->flatMap(fn ($p) => is_array($p->tags ?? null) ? $p->tags : (
                    is_array($p['tags'] ?? null) ? $p['tags'] : []
                ))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            return compact('industries', 'types', 'tags');
        });

        return response()->json([
            'data'    => $meta,
            'message' => 'Project meta retrieved successfully',
            'success' => true,
        ]);
    }
}
