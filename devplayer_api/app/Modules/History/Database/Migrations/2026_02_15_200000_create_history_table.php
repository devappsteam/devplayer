<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('channel_id');
            $table->enum('content_type', ['channel', 'movie', 'series']);
            $table->timestamp('watched_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'watched_at']);
            $table->index(['user_id', 'content_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};
