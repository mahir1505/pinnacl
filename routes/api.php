<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\ScoreController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PremiumController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// OAuth callbacks (no auth required - user redirected from provider)
Route::get('/social/callback/{platform}', [SocialController::class, 'callback']);

// Public share endpoint
Route::get('/share/{accountId}', [ShareController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Social accounts
    Route::get('/social/platforms', [SocialController::class, 'platforms']);
    Route::get('/social/connect/{platform}', [SocialController::class, 'connect']);
    Route::post('/social/link', [SocialController::class, 'link']);
    Route::get('/social/accounts', [SocialController::class, 'accounts']);
    Route::delete('/social/accounts/{id}', [SocialController::class, 'disconnect']);
    Route::post('/social/accounts/{id}/sync', [SocialController::class, 'sync']);

    // Scores
    Route::post('/scores/calculate/{accountId}', [ScoreController::class, 'calculate']);
    Route::get('/scores/{accountId}', [ScoreController::class, 'show']);
    Route::get('/scores/{accountId}/history', [ScoreController::class, 'history']);

    // Statistics
    Route::get('/stats/{accountId}', [StatsController::class, 'show']);
    Route::get('/stats/{accountId}/snapshots', [StatsController::class, 'snapshots']);
    Route::get('/stats/{accountId}/posts', [StatsController::class, 'posts']);
    Route::get('/stats/{accountId}/posts/{postId}', [StatsController::class, 'postDetail']);
    Route::get('/stats/{accountId}/content-breakdown', [StatsController::class, 'contentBreakdown']);

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'userDetail']);
        Route::get('/users/{id}/profiles', [AdminController::class, 'userProfiles']);
        Route::post('/reviews', [AdminController::class, 'createReview']);
        Route::get('/reviews/pending', [AdminController::class, 'pendingReviews']);
        Route::get('/reviews', [AdminController::class, 'allReviews']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
    });

    // Premium / Billing
    Route::get('/premium/status', [PremiumController::class, 'status']);
    Route::post('/premium/checkout', [PremiumController::class, 'checkout']);
    Route::post('/premium/cancel', [PremiumController::class, 'cancel']);

    // Settings
    Route::get('/settings', [AuthController::class, 'settings']);
    Route::put('/settings', [AuthController::class, 'updateSettings']);
});

// Stripe webhook (no auth, verified via signature)
Route::post('/webhook/stripe', [PremiumController::class, 'webhook']);
