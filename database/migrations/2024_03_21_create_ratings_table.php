<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('place_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->decimal('rating', 2, 1);
            $table->text('comment')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['place_id', 'user_id']);
            $table->unique(['place_id', 'ip_address']);
            $table->index(['place_id', 'created_at']);
            $table->index('user_id');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
}; 