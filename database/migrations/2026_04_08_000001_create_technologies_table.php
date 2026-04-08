<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();

            // The canonical display name — this is what gets stored in
            // projects.technologies[] and owner.tech_stack[].technology
            // e.g. "Vue.js", "FastAPI", "Laravel"
            $table->string('name')->unique();

            // URL-safe slug used for filtering: /laravel, /python, ?tech=laravel,python
            // e.g. "vuejs", "fastapi", "laravel"
            $table->string('slug')->unique();

            // ── Devicon ──────────────────────────────────────────────────────
            // The devicon identifier (lowercase, no dots/spaces)
            // e.g. "laravel", "vuejs", "fastapi" — null if not in devicon
            $table->string('devicon_name')->nullable();

            // Which devicon variant to use: "plain", "original", "plain-wordmark" etc.
            $table->string('devicon_version')->default('plain');

            // Whether to append the "colored" modifier to the class
            $table->boolean('devicon_colored')->default(true);

            // ── Ecosystem / category ─────────────────────────────────────────
            // This is the core of the smart filtering system.
            // FastAPI → category "python" means /python shows FastAPI projects.
            // Laravel → category "php" means /php shows Laravel projects.
            // You manage this from the admin panel — no code changes ever.
            $table->string('category')->default('other');

            // ── Brand color ──────────────────────────────────────────────────
            // Hex color for filter pills, tooltips etc. e.g. "#FF2D20"
            $table->string('color')->nullable();

            // ── Aliases ──────────────────────────────────────────────────────
            // All the ways a user might type this technology.
            // "Vue.js" aliases: ["vue", "vuejs", "vue.js", "vue js"]
            // The resolver matches any alias to the canonical record.
            $table->json('aliases')->nullable();

            // ── Fallback icon ────────────────────────────────────────────────
            // Used when devicon_name is null — a URL to an SVG or PNG
            $table->string('custom_icon_url')->nullable();

            // ── Management flags ─────────────────────────────────────────────
            // is_active: false hides it from the catalog without deleting it
            $table->boolean('is_active')->default(true);

            // is_manual: true prevents the daily sync command from overwriting
            // your custom edits (name, category, color, aliases etc.)
            $table->boolean('is_manual')->default(false);

            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('devicon_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};
