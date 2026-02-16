<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_progress', function (Blueprint $table) {
            $table->id();
            $table->string('sync_id')->index(); // UUID for grouping sync operations
            $table->foreignId('iptv_id')->constrained('i_p_t_v_s')->onDelete('cascade');
            $table->enum('type', ['live', 'vod', 'series'])->index();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('current_step')->nullable(); // categories, streams
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Composite indexes for better performance
            $table->index(['sync_id', 'type']);
            $table->index(['iptv_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_progress');
    }
};
