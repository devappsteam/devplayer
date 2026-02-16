<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->text('url');
            $table->enum('protocol', ['hls', 'dash', 'rtmp', 'rtsp', 'http_progressive'])->default('hls');
            $table->enum('quality', ['auto', 'low', 'medium', 'high', 'full_hd', 'uhd_4k'])->default('auto');
            $table->integer('bitrate')->nullable();
            $table->integer('height')->nullable();
            $table->integer('width')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->enum('health_status', ['online', 'offline', 'slow', 'unstable', 'excellent'])->default('online');
            $table->timestamp('last_check_at')->nullable();
            $table->integer('latency')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['channel_id', 'is_primary']);
            $table->index(['health_status', 'last_check_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
