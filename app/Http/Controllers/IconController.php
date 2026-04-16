<?php

namespace App\Http\Controllers;

use App\Models\Icon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IconController extends Controller
{
    /**
     * GET /api/icons
     * GET /api/icons?search=music
     * GET /api/icons?category=social
     *
     * Full catalog is cached forever (busted when any Icon record changes).
     * Search and category requests bypass cache — they're for the admin picker.
     *
     * SVGs are included in search results (max 100) so the Filament visual
     * picker can render live previews. They are NOT included in the full
     * catalog response (too large — ~1400 SVGs would bloat the response).
     */
    public function index(Request $request)
    {
        $search   = $request->query('search');
        $category = $request->query('category');

        if ($search || $category) {
            $query = Icon::active();

            if ($search) {
                $query->search($search);
            }

            if ($category) {
                $query->byCategory($category);
            }

            // Include svg here — the visual picker needs it
            $icons = $query->orderBy('name')
                ->limit(100)
                ->get(['name', 'keywords', 'category', 'svg']);

            return response()->json([
                'data'    => $icons,
                'message' => 'Icons retrieved successfully',
                'success' => true,
            ]);
        }

        // Full catalog — no SVGs to keep payload small
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
     * Returns the raw SVG for a single icon.
     */
    public function svg(string $name)
    {
        $icon = Icon::active()->where('name', $name)->first(['name', 'svg']);

        if (!$icon || !$icon->svg) {
            return response()->json(['message' => 'SVG not available', 'success' => false], 404);
        }

        return response($icon->svg, 200)->header('Content-Type', 'image/svg+xml');
    }
}
