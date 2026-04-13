<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications to users via Expo's push notification service.
 */
class PushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (! $user->expo_push_token) {
            return;
        }

        try {
            $response = Http::timeout(10)->withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post(self::EXPO_PUSH_URL, [
                'to' => $user->expo_push_token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'priority' => 'high',
            ]);

            if (! $response->successful()) {
                Log::warning('Expo push send failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning('Expo push exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
