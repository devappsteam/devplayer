<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('favorites', 'stream_type')) {
                $table->enum('stream_type', ['live', 'vod', 'series'])->default('live')->after('channel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            if (Schema::hasColumn('favorites', 'stream_type')) {
                $table->dropColumn('stream_type');
            }
        });
    }
};
