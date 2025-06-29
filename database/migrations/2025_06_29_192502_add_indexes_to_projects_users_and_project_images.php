<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // projects
        Schema::table('projects', function (Blueprint $table) {
            $table->index('slug', 'idx_slug');
            $table->index('is_featured', 'idx_is_featured');
            $table->index('status', 'idx_status');
            $table->index('type', 'idx_type');
        });

        // users
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_owner', 'idx_is_owner');
        });

        // project_images
        Schema::table('project_images', function (Blueprint $table) {
            $table->index('project_id', 'idx_project_id');
        });
    }

    public function down(): void
    {
        // projects
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_slug');
            $table->dropIndex('idx_is_featured');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_type');
        });

        // users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_is_owner');
        });

        // project_images
        Schema::table('project_images', function (Blueprint $table) {
            $table->dropIndex('idx_project_id');
        });
    }
};
