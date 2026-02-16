<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('iptv_id')->constrained('i_p_t_v_s')->onDelete('cascade');
            $table->enum('provider_type', ['xtream', 'm3u', 'm3u_url']);
            $table->longText('raw_data')->nullable();
            $table->json('normalized_data')->nullable();
            $table->integer('channels_count')->default(0);
            $table->integer('categories_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('checksum')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['iptv_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
