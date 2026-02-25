<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('followers')->default(0);
            $table->unsignedBigInteger('following')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->unsignedBigInteger('avg_likes')->default(0);
            $table->unsignedBigInteger('avg_views')->default(0);
            $table->unsignedBigInteger('avg_comments')->default(0);
            $table->unsignedInteger('total_posts')->default(0);
            $table->decimal('posting_frequency', 5, 2)->default(0); // posts per week
            $table->date('snapshot_date');
            $table->timestamps();

            $table->unique(['social_account_id', 'snapshot_date']);
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_snapshots');
    }
};
