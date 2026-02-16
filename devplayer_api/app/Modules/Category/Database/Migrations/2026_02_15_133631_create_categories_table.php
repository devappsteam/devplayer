<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('iptv_id')->constrained('i_p_t_v_s')->onDelete('cascade');
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->integer('order')->default(0);
            $table->integer('channels_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['iptv_id', 'order']);
            $table->unique(['iptv_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
