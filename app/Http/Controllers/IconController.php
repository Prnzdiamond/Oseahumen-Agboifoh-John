<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IconController extends Controller
{
    /**
     * GET /api/icons
     *
     * Returns the full active icon catalog for the frontend.
     * The frontend uses this to render Lucide icons by name.
     *
     * Optional query param: ?search=music
     * Returns icons whose name or keywords contain the search term.
     * Used when Phase 2 adds the visual icon picker to Filament.
     *
     * Optional query param: ?category=social
     * Returns icons filtered to a specific category.
     *
     * The SVG field is excluded from the public API response —
     * SVGs are only needed by the Filament admin picker, not the frontend
     * (the frontend uses @lucide/vue or lucide-react directly).
     *
     * Response shape per item:
     * {
     *   "name": "gamepad-2",
     *   "keywords": ["game", "gaming", "controller", "play", "joystick"],
     *   "category": "gaming"
     * }
     */
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $category = $request->query('category');

        // If searching or filtering by category, bypass cache and query directly
        if ($search || $category) {
            $query = Icon::active();

            if ($search) {
                $query->search($search);
            }

            if ($category) {
                $query->byCategory($category);
            }

            $icons = $query->orderBy('name')
                ->limit(100)
                ->get(['name', 'keywords', 'category']);

            return response()->json([
                'data'    => $icons,
                'message' => 'Icons retrieved successfully',
                'success' => true,
            ]);
        }

        // Full catalog — cached forever, invalidated when any Icon record changes
        $catalog = Cache::rememberForever('icons.catalog', function () {
            return Icon::active()
                ->orderBy('name')
                ->get(['name', 'keywords', 'category']);
        });

        return response()->json([
            'data'    => $catalog,
            'message' => 'Icons retrieved successfully',
            'success' => true,
        ]);
    }

    /**
     * GET /api/icons/{name}/svg
     *
     * Returns the raw SVG for a single icon by name.
     * Used by the Filament visual icon picker to render previews.
     * NOT needed by the public frontend.
     *
     * This endpoint is restricted to admin/internal use — add auth
     * middleware in Phase 2 when the picker is built.
     */
    public function svg(string $name)
    {
        $icon = Icon::active()->where('name', $name)->first(['name', 'svg']);

        if (!$icon) {
            return response()->json(['message' => 'Icon not found', 'success' => false], 404);
        }

        if (!$icon->svg) {
            return response()->json(['message' => 'SVG not available', 'success' => false], 404);
        }

        // Return as SVG content type for direct embedding
        return response($icon->svg, 200)->header('Content-Type', 'image/svg+xml');
    }
}
