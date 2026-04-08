<?php

namespace App\Console\Commands;

use App\Models\Technology;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fetches the official Devicon JSON catalog and upserts every entry
 * into the technologies table.
 *
 * Run manually:  php artisan technologies:sync
 * Run forced:    php artisan technologies:sync --force
 *
 * Scheduled:     Daily at 02:00 UTC (see app/Console/Kernel.php)
 *
 * Rules:
 *  - Records where is_manual = true are NEVER overwritten by this command,
 *    so any edits you make from the admin panel are safe.
 *  - With --force, even manual records are refreshed from Devicon data,
 *    but your custom name/slug/color/aliases are preserved if you have them.
 *  - New technologies in Devicon appear automatically after the next sync.
 *  - Technologies you add manually (e.g. Filament, Inertia) are preserved.
 */
class SyncDeviconTechnologies extends Command
{
    protected $signature = 'technologies:sync {--force : Re-sync even manually managed records}';
    protected $description = 'Sync the technology catalog from the Devicon JSON source';

    // ── Category inference map ────────────────────────────────────────────────
    // Maps a devicon identifier → your ecosystem category.
    // When you filter /python, ALL techs with category "python" are included.
    // This is the only place category assignments are defined — in code —
    // but only for auto-synced records. You can override any category
    // from the admin panel (those records get is_manual = true).
    private array $categoryMap = [
        // PHP ecosystem
        'php' => 'php',
        'laravel' => 'php',
        'symfony' => 'php',
        'wordpress' => 'php',
        'composer' => 'php',
        'codeigniter' => 'php',
        'yii' => 'php',
        'cakephp' => 'php',
        'slim' => 'php',

        // Python ecosystem
        'python' => 'python',
        'fastapi' => 'python',
        'django' => 'python',
        'flask' => 'python',
        'jupyter' => 'python',
        'numpy' => 'python',
        'pandas' => 'python',
        'pytorch' => 'python',
        'tensorflow' => 'python',
        'opencv' => 'python',
        'sqlalchemy' => 'python',
        'celery' => 'python',
        'pytest' => 'python',

        // JavaScript ecosystem
        'javascript' => 'javascript',
        'typescript' => 'javascript',
        'react' => 'javascript',
        'vuejs' => 'javascript',
        'angularjs' => 'javascript',
        'angular' => 'javascript',
        'nodejs' => 'javascript',
        'express' => 'javascript',
        'nextjs' => 'javascript',
        'nuxtjs' => 'javascript',
        'svelte' => 'javascript',
        'jquery' => 'javascript',
        'bun' => 'javascript',
        'deno' => 'javascript',
        'nestjs' => 'javascript',
        'remix' => 'javascript',
        'astro' => 'javascript',
        'solidjs' => 'javascript',
        'qwik' => 'javascript',
        'alpinejs' => 'javascript',
        'rxjs' => 'javascript',
        'redux' => 'javascript',
        'vitest' => 'javascript',
        'jest' => 'javascript',
        'electron' => 'javascript',
        'webpack' => 'javascript',
        'vite' => 'javascript',
        'babel' => 'javascript',
        'eslint' => 'javascript',

        // CSS / styling
        'css3' => 'css',
        'tailwindcss' => 'css',
        'bootstrap' => 'css',
        'sass' => 'css',
        'less' => 'css',
        'materialui' => 'css',
        'antdesign' => 'css',
        'storybook' => 'css',
        'figma' => 'css',

        // HTML
        'html5' => 'html',

        // Databases
        'mysql' => 'database',
        'postgresql' => 'database',
        'mongodb' => 'database',
        'redis' => 'database',
        'sqlite' => 'database',
        'mariadb' => 'database',
        'microsoftsqlserver' => 'database',
        'firebase' => 'database',
        'supabase' => 'database',
        'cassandra' => 'database',
        'elasticsearch' => 'database',
        'couchdb' => 'database',
        'neo4j' => 'database',
        'dynamodb' => 'database',
        'cockroachdb' => 'database',
        'oracle' => 'database',
        'prisma' => 'database',
        'sequelize' => 'database',

        // DevOps / Cloud
        'docker' => 'devops',
        'kubernetes' => 'devops',
        'amazonwebservices' => 'devops',
        'azure' => 'devops',
        'googlecloud' => 'devops',
        'nginx' => 'devops',
        'apache' => 'devops',
        'linux' => 'devops',
        'ubuntu' => 'devops',
        'debian' => 'devops',
        'centos' => 'devops',
        'bash' => 'devops',
        'terraform' => 'devops',
        'ansible' => 'devops',
        'jenkins' => 'devops',
        'github' => 'devops',
        'gitlab' => 'devops',
        'bitbucket' => 'devops',
        'git' => 'devops',
        'vercel' => 'devops',
        'netlify' => 'devops',
        'heroku' => 'devops',
        'digitalocean' => 'devops',
        'vagrant' => 'devops',
        'grafana' => 'devops',
        'prometheus' => 'devops',
        'circleci' => 'devops',
        'travisci' => 'devops',

        // Mobile
        'flutter' => 'mobile',
        'dart' => 'mobile',
        'swift' => 'mobile',
        'kotlin' => 'mobile',
        'reactnative' => 'mobile',
        'androidstudio' => 'mobile',
        'xcode' => 'mobile',
        'ionic' => 'mobile',
        'capacitor' => 'mobile',
        'expo' => 'mobile',

        // Systems languages
        'rust' => 'systems',
        'go' => 'systems',
        'c' => 'systems',
        'cplusplus' => 'systems',
        'csharp' => 'systems',
        'java' => 'systems',
        'scala' => 'systems',
        'clojure' => 'systems',
        'elixir' => 'systems',
        'erlang' => 'systems',
        'haskell' => 'systems',
        'lua' => 'systems',
        'perl' => 'systems',
        'ruby' => 'systems',
        'zig' => 'systems',
        'nim' => 'systems',
        'crystal' => 'systems',

        // Data / ML / AI
        'apachekafka' => 'data',
        'apachespark' => 'data',
        'hadoop' => 'data',
        'dbt' => 'data',
        'tableau' => 'data',
        'r' => 'data',
        'matlab' => 'data',
    ];

    // ── Preferred display names (when devicon key differs from common name) ───
    private array $displayNames = [
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'vuejs' => 'Vue.js',
        'nuxtjs' => 'Nuxt.js',
        'nextjs' => 'Next.js',
        'nodejs' => 'Node.js',
        'tailwindcss' => 'Tailwind CSS',
        'css3' => 'CSS',
        'html5' => 'HTML',
        'postgresql' => 'PostgreSQL',
        'mongodb' => 'MongoDB',
        'amazonwebservices' => 'AWS',
        'microsoftsqlserver' => 'SQL Server',
        'cplusplus' => 'C++',
        'csharp' => 'C#',
        'googlecloud' => 'Google Cloud',
        'reactnative' => 'React Native',
        'androidstudio' => 'Android Studio',
        'angularjs' => 'Angular',
        'apachekafka' => 'Apache Kafka',
        'apachespark' => 'Apache Spark',
        'solidjs' => 'SolidJS',
        'alpinejs' => 'Alpine.js',
        'nestjs' => 'NestJS',
        'cockroachdb' => 'CockroachDB',
        'materialui' => 'Material UI',
        'antdesign' => 'Ant Design',
    ];

    // ── Extra aliases per devicon name ────────────────────────────────────────
    private array $extraAliases = [
        'vuejs' => ['vue', 'vue.js', 'vue js', 'vuejs'],
        'nuxtjs' => ['nuxt', 'nuxt.js', 'nuxtjs'],
        'nextjs' => ['next', 'next.js', 'nextjs'],
        'nodejs' => ['node', 'node.js', 'nodejs'],
        'javascript' => ['js', 'javascript', 'ecmascript', 'es6'],
        'typescript' => ['ts', 'typescript'],
        'tailwindcss' => ['tailwind', 'tailwind css', 'tailwindcss'],
        'css3' => ['css', 'css3'],
        'html5' => ['html', 'html5'],
        'amazonwebservices' => ['aws', 'amazon web services', 'amazon aws'],
        'cplusplus' => ['c++', 'cpp'],
        'csharp' => ['c#', 'dotnet', '.net', 'cs'],
        'fastapi' => ['fastapi', 'fast api'],
        'postgresql' => ['postgres', 'postgresql', 'pgsql'],
        'mongodb' => ['mongo', 'mongodb'],
        'reactnative' => ['react native', 'reactnative'],
        'googlecloud' => ['gcp', 'google cloud', 'google cloud platform'],
        'angularjs' => ['angular', 'angularjs', 'angular js'],
        'nestjs' => ['nestjs', 'nest.js', 'nest js'],
        'solidjs' => ['solidjs', 'solid.js', 'solid js'],
        'alpinejs' => ['alpinejs', 'alpine.js', 'alpine js'],
    ];

    // ── Preferred devicon versions per identifier ─────────────────────────────
    // Override the auto-detected version for specific techs where
    // "plain" doesn't look great
    private array $versionOverrides = [
        'amazonwebservices' => 'original',
        'github' => 'original',
        'googlecloud' => 'plain',
        'angularjs' => 'plain',
        'react' => 'original',
        'nextjs' => 'original',
        'vuejs' => 'plain',
        'nuxtjs' => 'plain',
    ];

    public function handle(): int
    {
        $this->info('Fetching Devicon catalog from GitHub...');

        try {
            $response = Http::timeout(30)->get(
                'https://raw.githubusercontent.com/devicons/devicon/master/devicon.json'
            );
        } catch (\Exception $e) {
            $this->error('HTTP request failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $catalog = $response->json();
        $this->info('Fetched ' . count($catalog) . ' entries. Syncing...');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($catalog as $entry) {
            $deviconName = $entry['name'] ?? null;
            if (!$deviconName)
                continue;

            $displayName = $this->displayNames[$deviconName] ?? ucwords(str_replace('-', ' ', $deviconName));
            $slug = Str::slug(preg_replace('/[\.\+#]/', '', $displayName));

            // 1. Broad Search: Check all unique fields
            $existing = Technology::where('devicon_name', $deviconName)
                ->orWhere('slug', $slug)
                ->orWhere('name', $displayName)
                ->first();

            if ($existing?->is_manual && !$this->option('force')) {
                $skipped++;
                continue;
            }

            // 2. Prepare Data
            $fontVersions = $entry['versions']['font'] ?? [];
            $version = $this->versionOverrides[$deviconName] ?? ($fontVersions[0] ?? 'plain');
            $aliases = array_unique(array_merge(
                [$deviconName, strtolower($displayName)],
                $this->extraAliases[$deviconName] ?? []
            ));

            $attributes = [
                'name' => $existing?->name ?? $displayName,
                'slug' => $existing?->slug ?? $slug,
                'devicon_name' => $existing?->devicon_name ?? $deviconName,
                'devicon_version' => $version,
                'devicon_colored' => true,
                'color' => $entry['color'] ?? null,
                'category' => $this->categoryMap[$deviconName] ?? 'other',
                'aliases' => array_values($aliases),
                'is_active' => true,
                'is_manual' => false,
            ];

            // 3. The "Magic" Key: Update by ID if found, otherwise by devicon_name
            $searchKey = $existing ? ['id' => $existing->id] : ['devicon_name' => $deviconName];

            Technology::updateOrCreate($searchKey, $attributes);

            $existing ? $updated++ : $created++;
        }

        $manualCount = $this->seedManualExtras();
        $this->info("Sync complete.");
        return self::SUCCESS;
    }
    /**
     * Technologies that are important but not in the Devicon catalog.
     * These are created once (firstOrCreate) and flagged is_manual = true,
     * so the scheduler never touches them again unless you edit them.
     */
    private function seedManualExtras(): int
    {
        $extras = [
            [
                'name' => 'Filament',
                'slug' => 'filament',
                'devicon_name' => null,
                'color' => '#FDAE4B',
                'category' => 'php',
                'aliases' => ['filament', 'filamentphp', 'filament php'],
                'custom_icon_url' => null,
            ],
            [
                'name' => 'Inertia.js',
                'slug' => 'inertiajs',
                'devicon_name' => null,
                'color' => '#9553E9',
                'category' => 'javascript',
                'aliases' => ['inertia', 'inertia.js', 'inertiajs'],
                'custom_icon_url' => null,
            ],
            [
                'name' => 'Livewire',
                'slug' => 'livewire',
                'devicon_name' => null,
                'color' => '#FB70A9',
                'category' => 'php',
                'aliases' => ['livewire'],
                'custom_icon_url' => null,
            ],
            [
                'name' => 'Pinia',
                'slug' => 'pinia',
                'devicon_name' => null,
                'color' => '#FFD859',
                'category' => 'javascript',
                'aliases' => ['pinia'],
                'custom_icon_url' => null,
            ],
            [
                'name' => 'Cloudinary',
                'slug' => 'cloudinary',
                'devicon_name' => 'cloudinary',
                'devicon_version' => 'original',
                'color' => '#3448C5',
                'category' => 'devops',
                'aliases' => ['cloudinary'],
                'custom_icon_url' => null,
            ],
        ];

        $count = 0;
        foreach ($extras as $extra) {
            $created = Technology::firstOrCreate(
                ['slug' => $extra['slug']],
                array_merge($extra, [
                    'devicon_version' => $extra['devicon_version'] ?? 'plain',
                    'devicon_colored' => true,
                    'is_manual' => true,
                    'is_active' => true,
                ])
            );
            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }
}