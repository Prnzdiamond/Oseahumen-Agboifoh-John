<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('key_features')->nullable()->after('technologies');
            $table->enum('status', ['planning', 'in_progress', 'completed', 'on_hold', 'cancelled'])
                ->default('completed')->after('key_features');
            $table->enum('type', ['web_application', 'mobile_app', 'desktop_app', 'api', 'library', 'tool', 'game', 'other'])
                ->default('web_application')->after('status');
            $table->json('source_code')->nullable()->after('type');
            $table->date('completion_date')->nullable()->after('source_code');
            $table->integer('duration')->nullable()->comment('Duration in days')->after('completion_date');
            $table->boolean('is_featured')->default(false)->after('duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'key_features',
                'status',
                'type',
                'source_code',
                'completion_date',
                'duration',
                'is_featured'
            ]);
        });
    }
};
