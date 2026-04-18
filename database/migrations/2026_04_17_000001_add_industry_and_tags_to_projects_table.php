<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Industry / domain — single string, not an enum so you can
            // add new industries from the admin panel without a migration.
            // e.g. "real-estate", "e-commerce", "ai-ml", "fintech", "saas"
            $table->string('industry')->nullable()->after('is_featured');

            // Feature tags — short URL-safe slugs the admin explicitly assigns.
            // Kept separate from key_features (which is free-text prose) so
            // these stay clean and filterable.
            // e.g. ["live-chat", "websockets", "payment", "real-time", "auth"]
            $table->json('tags')->nullable()->after('industry');

            $table->index('industry');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['industry']);
            $table->dropColumn(['industry', 'tags']);
        });
    }
};
