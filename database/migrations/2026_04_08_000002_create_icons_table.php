<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icons', function (Blueprint $table) {
            $table->id();

            // The Lucide icon identifier — this exact string is what gets stored
            // in hobbies[].icon, contact_info[].icon, key_features[].icon etc.
            // e.g. "gamepad-2", "music", "mail", "phone", "github"
            $table->string('name')->unique();

            // ── Searchability ─────────────────────────────────────────────────
            // This is what makes the admin visual picker "smart".
            // When admin types "game", they see gamepad-2, dice-5, joystick etc.
            // because "game" appears in their keywords.
            // Synced from Lucide's own tags/categories in their JSON catalog.
            $table->json('keywords')->nullable();

            // Broad grouping for the picker UI — lets admin browse by category
            // e.g. "communication", "nature", "tech", "arrows", "social", "ui"
            $table->string('category')->default('general');

            // The raw SVG markup from Lucide — stored so Filament can render
            // a live visual preview in the icon picker without any CDN call.
            // Wrapped in a standard viewBox="0 0 24 24" container.
            $table->text('svg')->nullable();

            // is_active: false removes it from the picker without deleting it
            $table->boolean('is_active')->default(true);

            // is_manual: true prevents the daily sync from overwriting edits
            $table->boolean('is_manual')->default(false);

            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icons');
    }
};
