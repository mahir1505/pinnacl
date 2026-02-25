<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // instagram, tiktok, youtube, x, linkedin
            $table->string('platform_user_id');
            $table->string('username');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->json('profile_data')->nullable(); // followers, following, bio, etc.
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'platform']);
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
