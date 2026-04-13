<?php

namespace App\Services;

use App\Models\AdminMessage;
use App\Models\User;

/**
 * Sends admin messages (direct or broadcast) and tracks reads.
 */
class MessagingService
{
    public function __construct(
        protected PushNotificationService $push,
    ) {}

    public function send(User $sender, ?int $recipientId, string $title, string $body, bool $sendPush = true): AdminMessage
    {
        $recipientCount = $recipientId
            ? 1
            : User::count();

        $message = AdminMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'title' => $title,
            'body' => $body,
            'sent_push' => $sendPush,
            'recipients_count' => $recipientCount,
        ]);

        if ($sendPush) {
            $this->dispatchPush($message);
        }

        return $message;
    }

    private function dispatchPush(AdminMessage $message): void
    {
        $data = [
            'type' => 'admin_message',
            'message_uuid' => $message->uuid,
        ];

        if ($message->recipient_id) {
            $user = User::find($message->recipient_id);
            if ($user) {
                $this->push->sendToUser($user, $message->title, $message->body, $data);
            }

            return;
        }

        // Broadcast — chunk to avoid memory issues
        User::whereNotNull('expo_push_token')
            ->select(['id', 'expo_push_token'])
            ->chunk(100, function ($users) use ($message, $data) {
                foreach ($users as $user) {
                    $this->push->sendToUser($user, $message->title, $message->body, $data);
                }
            });
    }

    public function unreadCount(User $user): int
    {
        return AdminMessage::query()
            ->where(function ($q) use ($user) {
                $q->whereNull('recipient_id')->orWhere('recipient_id', $user->id);
            })
            ->whereDoesntHave('readBy', fn ($q) => $q->where('users.id', $user->id))
            ->count();
    }

    /**
     * Count of support conversations with admin replies the user hasn't read yet.
     * "Unread" = last message was from admin AND user hasn't viewed since.
     */
    public function userSupportUnreadCount(User $user): int
    {
        return \App\Models\SupportConversation::query()
            ->where('user_id', $user->id)
            ->where('last_message_by_admin', true)
            ->where(function ($q) {
                $q->whereNull('user_last_read_at')
                    ->orWhereColumn('user_last_read_at', '<', 'last_message_at');
            })
            ->count();
    }

    /**
     * Total things needing the user's attention: unread broadcasts
     * plus support tickets where the admin has replied.
     */
    public function totalUnreadForUser(User $user): int
    {
        return $this->unreadCount($user) + $this->userSupportUnreadCount($user);
    }

    /**
     * Count of support conversations awaiting an admin reply.
     */
    public function adminSupportInboxCount(): int
    {
        return \App\Models\SupportConversation::query()
            ->where('status', 'awaiting_admin')
            ->count();
    }

    public function markAllRead(User $user): void
    {
        $unread = AdminMessage::query()
            ->where(function ($q) use ($user) {
                $q->whereNull('recipient_id')->orWhere('recipient_id', $user->id);
            })
            ->whereDoesntHave('readBy', fn ($q) => $q->where('users.id', $user->id))
            ->pluck('id');

        $now = now();
        $rows = $unread->map(fn ($id) => [
            'admin_message_id' => $id,
            'user_id' => $user->id,
            'read_at' => $now,
        ])->all();

        if (! empty($rows)) {
            \DB::table('admin_message_reads')->insert($rows);
        }
    }

    public function userMessages(User $user, int $limit = 20)
    {
        return AdminMessage::query()
            ->where(function ($q) use ($user) {
                $q->whereNull('recipient_id')->orWhere('recipient_id', $user->id);
            })
            ->with(['sender:id,name'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
