<?php

namespace App\Console\Commands;

use App\Models\Icon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the Lucide icon catalog and upserts every icon into the icons table.
 * Stores the SVG markup so the Filament visual picker can render previews
 * without any CDN dependency.
 *
 * Run manually:  php artisan icons:sync
 * Run forced:    php artisan icons:sync --force
 *
 * Scheduled:     Daily at 02:30 UTC (see app/Console/Kernel.php)
 *
 * Lucide ships a JSON file at:
 * https://unpkg.com/lucide-static@latest/tags.json
 * which maps icon-name → array of keyword tags.
 *
 * SVGs are fetched individually from:
 * https://unpkg.com/lucide-static@latest/icons/{name}.svg
 *
 * We batch the SVG fetching to avoid hammering the CDN — 50 at a time
 * with a short pause between batches.
 */
class SyncLucideIcons extends Command
{
    protected $signature   = 'icons:sync {--force : Re-sync even manually managed records} {--no-svg : Skip SVG fetching (faster, picker shows names only)}';
    protected $description = 'Sync the icon catalog from Lucide';

    // ── Category inference from tags ──────────────────────────────────────────
    // Lucide doesn't have formal categories, so we infer them from tags.
    // First matching keyword wins.
    private array $categoryKeywords = [
        'arrows'        => ['arrow', 'chevron', 'move', 'navigation', 'direction', 'pointer'],
        'communication' => ['mail', 'message', 'chat', 'phone', 'call', 'contact', 'talk', 'speech'],
        'social'        => ['github', 'twitter', 'linkedin', 'facebook', 'instagram', 'youtube', 'share', 'social'],
        'media'         => ['play', 'pause', 'music', 'audio', 'video', 'camera', 'image', 'photo', 'film', 'mic', 'speaker'],
        'tech'          => ['code', 'terminal', 'cpu', 'server', 'database', 'wifi', 'bluetooth', 'monitor', 'laptop', 'keyboard', 'mouse', 'hardware'],
        'files'         => ['file', 'folder', 'document', 'archive', 'attachment', 'clipboard', 'paperclip'],
        'data'          => ['chart', 'graph', 'bar', 'pie', 'trend', 'analytics', 'statistics'],
        'security'      => ['lock', 'unlock', 'shield', 'key', 'password', 'security', 'fingerprint'],
        'weather'       => ['sun', 'moon', 'cloud', 'rain', 'snow', 'wind', 'storm', 'weather', 'temperature'],
        'nature'        => ['tree', 'leaf', 'flower', 'plant', 'mountain', 'wave', 'nature'],
        'food'          => ['coffee', 'pizza', 'food', 'drink', 'cooking', 'restaurant', 'kitchen'],
        'travel'        => ['car', 'plane', 'ship', 'bus', 'bicycle', 'map', 'location', 'travel', 'compass'],
        'shopping'      => ['shop', 'cart', 'bag', 'gift', 'tag', 'package', 'box', 'store'],
        'health'        => ['heart', 'medical', 'health', 'hospital', 'pill', 'activity', 'pulse'],
        'education'     => ['book', 'graduation', 'school', 'education', 'pencil', 'pen', 'write'],
        'gaming'        => ['gamepad', 'game', 'dice', 'joystick', 'controller', 'trophy', 'puzzle'],
        'home'          => ['home', 'house', 'building', 'room', 'furniture', 'window', 'door'],
        'time'          => ['clock', 'calendar', 'time', 'date', 'alarm', 'schedule', 'history'],
        'ui'            => ['button', 'toggle', 'check', 'close', 'menu', 'grid', 'list', 'settings', 'filter', 'search', 'zoom'],
        'people'        => ['user', 'users', 'person', 'team', 'group', 'profile', 'contact'],
        'finance'       => ['dollar', 'money', 'credit', 'wallet', 'bank', 'coin', 'currency'],
        'tools'         => ['wrench', 'tool', 'hammer', 'settings', 'gear', 'build', 'construction'],
    ];

    public function handle(): int
    {
        $this->info('Fetching Lucide icon tags from unpkg...');

        // ── Step 1: Fetch the tags.json (icon name → keywords mapping) ────────
        try {
            $tagsResponse = Http::timeout(30)->get(
                'https://unpkg.com/lucide-static@latest/tags.json'
            );
        } catch (\Exception $e) {
            $this->error('HTTP request failed: ' . $e->getMessage());
            Log::error('icons:sync tags fetch failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        if (!$tagsResponse->successful()) {
            $this->error("Lucide tags.json returned HTTP {$tagsResponse->status()}");
            return self::FAILURE;
        }

        $tagsMap = $tagsResponse->json();

        if (empty($tagsMap)) {
            $this->error('Lucide tags.json was empty or malformed.');
            return self::FAILURE;
        }

        $this->info('Found ' . count($tagsMap) . ' icons. Processing...');

        $skipSvg   = $this->option('no-svg');
        $created   = 0;
        $updated   = 0;
        $skipped   = 0;

        // ── Step 2: Process each icon ─────────────────────────────────────────
        $iconNames = array_keys($tagsMap);
        $chunks    = array_chunk($iconNames, 50); // Process 50 at a time

        $bar = $this->output->createProgressBar(count($iconNames));
        $bar->start();

        foreach ($chunks as $chunk) {
            foreach ($chunk as $iconName) {
                $keywords = $tagsMap[$iconName] ?? [];

                // Merge name words into keywords for better searchability
                // "gamepad-2" → add "gamepad", "game" to keywords
                $nameParts = explode('-', $iconName);
                $allKeywords = array_unique(array_merge($keywords, $nameParts));

                $category = $this->inferCategory($allKeywords, $iconName);

                $existing = Icon::where('name', $iconName)->first();

                if ($existing?->is_manual && !$this->option('force')) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $attributes = [
                    'keywords' => array_values($allKeywords),
                    'category' => $category,
                    'is_active' => true,
                    'is_manual' => false,
                ];

                // ── Step 3: Fetch SVG if not skipped ─────────────────────────
                if (!$skipSvg && (!$existing || $this->option('force') || empty($existing->svg))) {
                    try {
                        $svgResponse = Http::timeout(10)->get(
                            "https://unpkg.com/lucide-static@latest/icons/{$iconName}.svg"
                        );
                        if ($svgResponse->successful()) {
                            $attributes['svg'] = $svgResponse->body();
                        }
                    } catch (\Exception $e) {
                        // Non-fatal: we just won't have the SVG preview for this icon
                        Log::warning("icons:sync SVG fetch failed for {$iconName}", ['error' => $e->getMessage()]);
                    }
                }

                Icon::updateOrCreate(['name' => $iconName], $attributes);

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }

                $bar->advance();
            }

            // Brief pause between batches to be polite to the CDN
            if (!$skipSvg) {
                usleep(100000); // 100ms
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync complete.");
        $this->table(
            ['Created', 'Updated', 'Skipped (manual)', 'SVGs fetched'],
            [[$created, $updated, $skipped, $skipSvg ? 'No (--no-svg)' : 'Yes']]
        );

        if ($skipSvg) {
            $this->warn('SVGs were skipped. Run without --no-svg to fetch them (required for visual picker).');
        }

        return self::SUCCESS;
    }

    /**
     * Infer a category from the icon's keywords + name.
     * Returns the first category whose keywords overlap with the icon's tags.
     */
    private function inferCategory(array $keywords, string $iconName): string
    {
        $keywordSet = array_map('strtolower', $keywords);
        $nameLower  = strtolower($iconName);

        foreach ($this->categoryKeywords as $category => $triggers) {
            foreach ($triggers as $trigger) {
                if (str_contains($nameLower, $trigger)) {
                    return $category;
                }
                foreach ($keywordSet as $kw) {
                    if (str_contains($kw, $trigger)) {
                        return $category;
                    }
                }
            }
        }

        return 'general';
    }
}
