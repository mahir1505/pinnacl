<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('platform_post_id');
            $table->string('post_type')->nullable(); // photo, video, reel, short, story
            $table->text('caption')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('post_url')->nullable();
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['social_account_id', 'platform_post_id']);
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
