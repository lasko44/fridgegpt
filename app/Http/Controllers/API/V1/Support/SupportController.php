<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Mobile API for user support conversations.
 */
class SupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()
            ->supportConversations()
            ->orderByDesc('last_message_at')
            ->paginate(15);

        return response()->json($conversations);
    }

    public function store(Request $request, SupportTicketService $service): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $service->open($request->user(), $validated['subject'], $validated['body']);

        return response()->json(['conversation' => $conversation], 201);
    }

    public function show(Request $request, string $reference): JsonResponse
    {
        $conversation = SupportConversation::where('reference', $reference)->firstOrFail();

        if ($conversation->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        $conversation->load(['messages.sender:id,name']);
        $conversation->update(['user_last_read_at' => now()]);

        return response()->json(['conversation' => $conversation]);
    }

    public function reply(Request $request, string $reference, SupportTicketService $service): JsonResponse
    {
        $conversation = SupportConversation::where('reference', $reference)->firstOrFail();

        if ($conversation->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        if (! $conversation->isOpen()) {
            return response()->json(['message' => 'This conversation is closed'], 422);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $service->replyAsUser($conversation, $request->user(), $validated['body']);

        return response()->json(['message' => $message]);
    }
}
