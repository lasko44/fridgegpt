<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Handles support ticket creation, replies, and notifications.
 */
class SupportTicketService
{
    public function __construct(
        protected PushNotificationService $push,
    ) {}

    public function open(User $user, string $subject, string $body): SupportConversation
    {
        return DB::transaction(function () use ($user, $subject, $body) {
            $conversation = SupportConversation::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'status' => SupportConversation::STATUS_AWAITING_ADMIN,
                'last_message_at' => now(),
                'last_message_by_admin' => false,
            ]);

            SupportMessage::create([
                'support_conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'is_admin' => false,
                'body' => $body,
            ]);

            $this->notifyAdmins($conversation, "New support ticket: {$subject}", $body);

            return $conversation->fresh();
        });
    }

    public function replyAsUser(SupportConversation $conversation, User $user, string $body): SupportMessage
    {
        $message = $this->createReply($conversation, $user, $body, false);

        $conversation->update([
            'status' => SupportConversation::STATUS_AWAITING_ADMIN,
            'last_message_at' => $message->created_at,
            'last_message_by_admin' => false,
        ]);

        $this->notifyAdmins($conversation, "Reply on: {$conversation->subject}", $body);

        return $message;
    }

    public function replyAsAdmin(SupportConversation $conversation, User $admin, string $body): SupportMessage
    {
        $message = $this->createReply($conversation, $admin, $body, true);

        $conversation->update([
            'status' => SupportConversation::STATUS_AWAITING_USER,
            'last_message_at' => $message->created_at,
            'last_message_by_admin' => true,
        ]);

        // Notify the user via push
        $this->push->sendToUser(
            $conversation->user,
            "Reply: {$conversation->subject}",
            $this->preview($body),
            ['type' => 'support_reply', 'conversation_uuid' => $conversation->uuid],
        );

        return $message;
    }

    public function updateStatus(SupportConversation $conversation, string $status): void
    {
        $conversation->update(['status' => $status]);
    }

    private function createReply(SupportConversation $conversation, User $sender, string $body, bool $isAdmin): SupportMessage
    {
        return SupportMessage::create([
            'support_conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'is_admin' => $isAdmin,
            'body' => $body,
        ]);
    }

    private function notifyAdmins(SupportConversation $conversation, string $title, string $body): void
    {
        $admins = User::query()
            ->where('is_admin', true)
            ->whereNotNull('expo_push_token')
            ->get(['id', 'expo_push_token']);

        $data = [
            'type' => 'support_new',
            'conversation_uuid' => $conversation->uuid,
        ];

        foreach ($admins as $admin) {
            $this->push->sendToUser($admin, $title, $this->preview($body), $data);
        }
    }

    private function preview(string $body, int $length = 120): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($body));

        return mb_strlen($clean) > $length
            ? mb_substr($clean, 0, $length - 1) . '…'
            : $clean;
    }
}
