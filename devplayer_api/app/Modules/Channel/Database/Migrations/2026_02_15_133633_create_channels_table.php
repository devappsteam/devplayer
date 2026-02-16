<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('iptv_id')->constrained('i_p_t_v_s')->onDelete('cascade');
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->string('logo_url')->nullable();
            $table->text('stream_url');
            $table->enum('stream_type', ['live', 'vod', 'series'])->default('live');
            $table->string('epg_channel_id')->nullable();
            $table->integer('number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active']);
            $table->index(['iptv_id', 'stream_type']);
            $table->unique(['iptv_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
