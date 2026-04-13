<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores the device's Expo push token on the authenticated user.
 */
class RegisterPushTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'starts_with:ExponentPushToken[,ExpoPushToken['],
        ]);

        $user = $request->user();
        $user->update(['expo_push_token' => $validated['token']]);

        return response()->json(['status' => 'ok']);
    }
}
