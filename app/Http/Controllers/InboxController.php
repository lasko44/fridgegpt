<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use App\Models\SupportMessage;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User notification inbox — admin broadcasts, direct messages, and support replies.
 */
class InboxController extends Controller
{
    public function index(Request $request, MessagingService $messaging): Response
    {
        $user = $request->user();

        // Admin broadcasts + direct messages
        $adminMessages = $messaging->userMessages($user, 50)
            ->map(fn ($msg) => [
                'id' => "msg-{$msg->id}",
                'type' => 'message',
                'title' => $msg->title,
                'body' => $msg->body,
                'href' => null,
                'sender_name' => $msg->sender?->name ?? 'FridgeGPT',
                'is_broadcast' => $msg->recipient_id === null,
                'created_at' => $msg->created_at->toISOString(),
            ]);

        // Support replies from admins — only from conversations the user hasn't read yet
        $supportReplies = SupportMessage::query()
            ->where('is_admin', true)
            ->whereHas('conversation', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('last_message_by_admin', true)
                ->where(function ($inner) {
                    $inner->whereNull('user_last_read_at')
                        ->orWhereColumn('user_last_read_at', '<', 'last_message_at');
                })
            )
            ->with(['conversation:id,reference,subject', 'sender:id,name'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($msg) => [
                'id' => "support-{$msg->id}",
                'type' => 'support_reply',
                'title' => "Re: {$msg->conversation->subject}",
                'body' => $msg->body,
                'href' => "/support/{$msg->conversation->reference}",
                'sender_name' => $msg->sender?->name ?? 'Support',
                'is_broadcast' => false,
                'created_at' => $msg->created_at->toISOString(),
            ]);

        // Merge and sort newest first
        $notifications = $adminMessages->concat($supportReplies)
            ->sortByDesc('created_at')
            ->values()
            ->take(50);

        // Mark admin messages as read
        $messaging->markAllRead($user);

        return Inertia::render('Inbox/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function unreadCount(Request $request, MessagingService $messaging): JsonResponse
    {
        return response()->json([
            'count' => $messaging->totalUnreadForUser($request->user()),
        ]);
    }
}
