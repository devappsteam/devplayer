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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('type', 20)->default('live')->after('name');
            $table->index(['iptv_id', 'type']);
        });

        // Update unique constraint to include type
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['iptv_id', 'external_id']);
            $table->unique(['iptv_id', 'external_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['iptv_id', 'external_id', 'type']);
            $table->unique(['iptv_id', 'external_id']);
            $table->dropIndex(['iptv_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
