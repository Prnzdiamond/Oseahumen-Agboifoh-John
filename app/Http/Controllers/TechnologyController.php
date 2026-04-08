<?php

namespace App\Http\Controllers;

use App\Models\Technology;
use Illuminate\Support\Facades\Cache;

class TechnologyController extends Controller
{
    /**
     * GET /api/technologies
     *
     * Returns the full active technology catalog.
     * Cached forever — invalidated automatically whenever any Technology
     * record is saved or deleted (see Technology model boot()).
     *
     * The frontend uses this response to:
     *  1. Resolve any tech name string → devicon class + color + category
     *  2. Build the filter pill bar on /projects (only shows techs that
     *     actually appear in your projects — but needs the full catalog
     *     to resolve names to slugs and categories)
     *  3. Power the ?tech= URL filter with category expansion
     *     e.g. "python" → expand to all slugs with category "python"
     *
     * Response shape per item:
     * {
     *   "id": 1,
     *   "name": "Laravel",
     *   "slug": "laravel",
     *   "devicon_name": "laravel",
     *   "devicon_class": "devicon-laravel-plain colored",
     *   "color": "#FF2D20",
     *   "category": "php",
     *   "aliases": ["laravel"],
     *   "custom_icon_url": null
     * }
     */
    public function index()
    {
        $catalog = Cache::rememberForever('technologies.catalog', function () {
            return Technology::active()
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->map(fn ($t) => [
                    'id'              => $t->id,
                    'name'            => $t->name,
                    'slug'            => $t->slug,
                    'devicon_name'    => $t->devicon_name,
                    'devicon_class'   => $t->getDeviconClass(),
                    'color'           => $t->color,
                    'category'        => $t->category,
                    'aliases'         => $t->aliases ?? [],
                    'custom_icon_url' => $t->custom_icon_url,
                ]);
        });

        return response()->json([
            'data'    => $catalog,
            'message' => 'Technologies retrieved successfully',
            'success' => true,
        ]);
    }
}
