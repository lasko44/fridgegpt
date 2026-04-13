<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Services\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * User-facing support ticket controller.
 */
class SupportConversationController extends Controller
{
    public function index(Request $request): Response
    {
        $conversations = $request->user()
            ->supportConversations()
            ->orderByDesc('last_message_at')
            ->paginate(15);

        return Inertia::render('Support/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Support/Create');
    }

    public function store(Request $request, SupportTicketService $service): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $service->open(
            $request->user(),
            $validated['subject'],
            $validated['body'],
        );

        return redirect()->route('support.show', $conversation->reference);
    }

    public function show(Request $request, SupportConversation $conversation): Response
    {
        if ($conversation->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        $conversation->load(['messages.sender:id,name']);

        // Mark as read — clears the notification badge
        $conversation->update(['user_last_read_at' => now()]);

        return Inertia::render('Support/Show', [
            'conversation' => $conversation,
        ]);
    }

    public function reply(Request $request, SupportConversation $conversation, SupportTicketService $service): RedirectResponse
    {
        if ($conversation->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        if (! $conversation->isOpen()) {
            return back()->with('flash', ['error' => 'This conversation is closed']);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $service->replyAsUser($conversation, $request->user(), $validated['body']);

        return back();
    }
}
