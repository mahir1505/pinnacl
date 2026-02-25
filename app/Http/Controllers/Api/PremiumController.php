<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PremiumController extends Controller
{
    /**
     * Get premium status for the current user.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'is_premium' => $user->isPremium(),
            'premium_expires_at' => $user->premium_expires_at?->toIso8601String(),
            'plan' => $user->isPremium() ? 'premium' : 'free',
        ]);
    }

    /**
     * Create a checkout session (Stripe placeholder).
     *
     * In production, this would create a Stripe Checkout Session.
     * For now, it activates premium directly for demo purposes.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => 'required|in:monthly,yearly',
        ]);

        // In production: create Stripe checkout session
        // $session = \Stripe\Checkout\Session::create([...]);
        // return response()->json(['checkout_url' => $session->url]);

        // Demo mode: activate premium directly
        $user = $request->user();
        $duration = $validated['plan'] === 'yearly' ? 365 : 30;

        $user->is_premium = true;
        $user->premium_expires_at = now()->addDays($duration);
        $user->save();

        return response()->json([
            'message' => 'Premium activated successfully.',
            'is_premium' => true,
            'premium_expires_at' => $user->premium_expires_at->toIso8601String(),
        ]);
    }

    /**
     * Cancel premium subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPremium()) {
            return response()->json(['message' => 'No active premium subscription.'], 400);
        }

        // In production: cancel Stripe subscription
        // The user remains premium until expiration
        return response()->json([
            'message' => 'Subscription cancelled. Premium access continues until ' . $user->premium_expires_at->toDateString(),
            'premium_expires_at' => $user->premium_expires_at->toIso8601String(),
        ]);
    }

    /**
     * Stripe webhook handler (placeholder).
     */
    public function webhook(Request $request): JsonResponse
    {
        // In production: verify Stripe webhook signature
        // Handle events: checkout.session.completed, invoice.paid, customer.subscription.deleted, etc.

        return response()->json(['received' => true]);
    }
}
