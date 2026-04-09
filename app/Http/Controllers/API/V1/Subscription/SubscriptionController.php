<?php

namespace App\Http\Controllers\Api\V1\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Requests\Subscription\CreateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API controller for handling subscription operations.
 */
class SubscriptionController extends Controller
{
    /**
     * Display the user's subscription details.
     */
    public function show(Request $request, SubscriptionService $subscriptionService): JsonResponse
    {
        $user = $request->user();
        $details = $subscriptionService->getSubscriptionDetails($user);

        return response()->json([
            'subscription' => new SubscriptionResource($details),
        ]);
    }

    /**
     * Create a new subscription.
     */
    public function store(CreateSubscriptionRequest $request, SubscriptionService $subscriptionService): JsonResponse
    {
        try {
            $subscriptionService->createSubscription(
                $request->user(),
                $request->getPaymentMethod()
            );

            return response()->json([
                'message' => 'Subscription created successfully!',
                'user' => new UserResource($request->user()->fresh()),
            ], 201);
        } catch (Exception $e) {
            Log::error('API Subscription creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create subscription. Please try again.',
            ], 422);
        }
    }

    /**
     * Cancel the user's subscription.
     */
    public function destroy(CancelSubscriptionRequest $request, SubscriptionService $subscriptionService): JsonResponse
    {
        try {
            $subscriptionService->cancelSubscription($request->user());

            return response()->json([
                'message' => 'Subscription cancelled successfully!',
                'user' => new UserResource($request->user()->fresh()),
            ]);
        } catch (Exception $e) {
            Log::error('API Subscription cancellation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cancel subscription.',
            ], 422);
        }
    }
}
