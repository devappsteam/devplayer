<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('episodes')) {
            Schema::create('episodes', function (Blueprint $table) {
                $table->id();
                $table->uuid()->unique()->index();
                $table->foreignId('channel_id')->constrained()->onDelete('cascade');
                $table->unsignedSmallInteger('season');
                $table->unsignedSmallInteger('episode');
                $table->string('name');
                $table->longText('description')->nullable();
                $table->longText('plot')->nullable();
                $table->date('aired_date')->nullable();
                $table->unsignedSmallInteger('duration')->nullable(); // em minutos
                $table->string('thumbnail_url')->nullable();
                $table->string('external_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();

                // Índices para buscas comuns
                $table->index(['channel_id', 'season', 'episode']);
                $table->index(['channel_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
