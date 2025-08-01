<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProjectResource;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // GET /api/projects
    public function index()
    {
        // Cache projects for 1 hour (3600 seconds)
        $projects = Cache::rememberForever('projects.all', function () {
            return Project::with([
                'images' => function ($query) {
                    $query->select('id', 'project_id', 'image');
                }
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
                    'created_at',
                    'updated_at'
                ])
                ->latest()
                ->get();
        });

        if ($projects->isEmpty()) {
            return response()->json([
                'data' => [],
                'message' => 'No projects found',
                'success' => true,
            ], 200);
        }

        $data = Cache::rememberForever('projects.transformed', function () use ($projects) {
            return ProjectResource::collection($projects)->resolve(); // Get raw array
        });

        return response()->json([
            'data' => $data,
            'message' => 'Projects retrieved successfully',
            'success' => true,
        ], 200);
    }

    /**
     * Display a specific project with caching
     */
    public function show(string $slug)
    {
        // Cache individual project for 30 minutes
        $project = Cache::rememberForever("project.{$slug}", function () use ($slug) {
            return Project::with([
                'images' => function ($query) {
                    $query->select('id', 'project_id', 'image');
                }
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
                    'created_at',
                    'updated_at'
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
            return new ProjectResource($project); // Get raw array
        });
        return response()->json([
            'data' => $data,
            'message' => 'Project retrieved successfully',
            'success' => true,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }
}
