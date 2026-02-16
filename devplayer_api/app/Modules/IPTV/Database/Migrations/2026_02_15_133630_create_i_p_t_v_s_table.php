<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('i_p_t_v_s', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('provider_type', ['xtream', 'm3u', 'm3u_url'])->default('xtream');
            $table->string('url');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('i_p_t_v_s');
    }
};
