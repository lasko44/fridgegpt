<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns recent notifications for the in-app notification poller.
 * Aggregates admin broadcasts and support replies into a single feed.
 */
class NotificationFeedController extends Controller
{
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        $since = $request->query('since')
            ? \Carbon\Carbon::createFromTimestamp((int) $request->query('since'))
            : now()->subDay();

        $notifications = [];

        // Admin broadcasts and direct messages addressed to this user
        $messages = AdminMessage::query()
            ->where(function ($q) use ($user) {
                $q->whereNull('recipient_id')->orWhere('recipient_id', $user->id);
            })
            ->where('created_at', '>=', $since)
            ->whereDoesntHave('readBy', fn ($q) => $q->where('users.id', $user->id))
            ->latest()
            ->limit(20)
            ->get();

        foreach ($messages as $msg) {
            $notifications[] = [
                'id' => "msg-{$msg->id}",
                'type' => 'message',
                'title' => $msg->title,
                'body' => $msg->body,
                'href' => '/inbox',
                'created_at' => $msg->created_at->timestamp,
            ];
        }

        // Admin replies on this user's support tickets since $since — only unread
        $replies = SupportMessage::query()
            ->where('is_admin', true)
            ->where('created_at', '>=', $since)
            ->whereHas('conversation', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where(function ($inner) {
                    $inner->whereNull('user_last_read_at')
                        ->orWhereColumn('user_last_read_at', '<', 'last_message_at');
                })
            )
            ->with('conversation:id,reference,subject')
            ->latest()
            ->limit(20)
            ->get();

        foreach ($replies as $reply) {
            $notifications[] = [
                'id' => "support-{$reply->id}",
                'type' => 'support_reply',
                'title' => 'Support replied',
                'body' => $reply->conversation->subject,
                'href' => "/support/{$reply->conversation->reference}",
                'created_at' => $reply->created_at->timestamp,
            ];
        }

        usort($notifications, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return response()->json(['notifications' => $notifications]);
    }

    public function admin(Request $request): JsonResponse
    {
        if (! $request->user()?->is_admin) {
            return response()->json(['notifications' => []]);
        }

        $since = $request->query('since')
            ? \Carbon\Carbon::createFromTimestamp((int) $request->query('since'))
            : now()->subHours(2);

        // New support messages from users since $since
        $userMessages = SupportMessage::query()
            ->where('is_admin', false)
            ->where('created_at', '>=', $since)
            ->with(['conversation:id,reference,subject', 'sender:id,name'])
            ->latest()
            ->limit(20)
            ->get();

        $notifications = $userMessages->map(fn ($msg) => [
            'id' => "support-msg-{$msg->id}",
            'type' => 'support_inbound',
            'title' => "New support message from {$msg->sender?->name}",
            'body' => $msg->conversation->subject,
            'href' => "/admin/support/{$msg->conversation->reference}",
            'created_at' => $msg->created_at->timestamp,
        ])->values();

        return response()->json(['notifications' => $notifications]);
    }
}
