<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history', function (Blueprint $table) {
            $table->integer('episode_id')->nullable()->after('channel_id');
            $table->integer('episode_season')->nullable()->after('episode_id');
            $table->integer('episode_number')->nullable()->after('episode_season');
            $table->string('episode_title')->nullable()->after('episode_number');
            $table->text('episode_description')->nullable()->after('episode_title');
            $table->string('episode_thumbnail')->nullable()->after('episode_description');
            $table->string('episode_stream_url')->nullable()->after('episode_thumbnail');

            $table->index(['user_id', 'content_type', 'watched_at']);
        });
    }

    public function down(): void
    {
        Schema::table('history', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'content_type', 'watched_at']);
            $table->dropColumn([
                'episode_id',
                'episode_season',
                'episode_number',
                'episode_title',
                'episode_description',
                'episode_thumbnail',
                'episode_stream_url',
            ]);
        });
    }
};
