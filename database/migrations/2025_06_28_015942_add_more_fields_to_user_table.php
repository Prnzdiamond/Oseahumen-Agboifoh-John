<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('professional_journey')->nullable()->after('urls');
            $table->json('hobbies')->nullable()->after('professional_journey');
            $table->json('languages')->nullable()->after('hobbies');
            $table->json('contact_info')->nullable()->after('languages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['professional_journey', 'hobbies', 'languages', 'contact_info']);
        });
    }
};
