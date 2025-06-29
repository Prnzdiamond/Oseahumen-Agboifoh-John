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
        Schema::table('users', function (Blueprint $table) {

                $table->string('avatar')->nullable();
                $table->string('headline')->nullable();        // short tagline
                $table->text('bio')->nullable();               // about-me paragraph
                $table->json('tech_stack')->nullable();        // ["Laravel","Vue","Tailwind"]
                $table->json('expertise')->nullable();         // ["Backend","DevOps"]
                $table->json('urls')->nullable();              // ["github_url", "linkedin_url", "twitter_url", "website_url"]
                // personal site / blog
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'headline',
                'bio',
                'tech_stack',
                'expertise',
                'urls'
            ]);
        });
    }
};
