<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Services\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin support inbox — list, view, reply, change status.
 */
class SupportConversationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SupportConversation::query()
            ->with(['user:id,uuid,name,email', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('messages');

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Search across reference, subject, message body, and user details
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%")
                            ->orWhere('username', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('messages', function ($mq) use ($search) {
                        $mq->where('body', 'ilike', "%{$search}%");
                    });
            });
        }

        // Default sort: most recently updated first
        $query->orderByDesc('last_message_at');

        $conversations = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Support/Index', [
            'conversations' => $conversations,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'counts' => [
                'all' => SupportConversation::count(),
                'awaiting_admin' => SupportConversation::where('status', 'awaiting_admin')->count(),
                'awaiting_user' => SupportConversation::where('status', 'awaiting_user')->count(),
                'resolved' => SupportConversation::where('status', 'resolved')->count(),
                'closed' => SupportConversation::where('status', 'closed')->count(),
            ],
        ]);
    }

    public function show(SupportConversation $conversation): Response
    {
        $conversation->load(['user:id,uuid,name,username,email,token_balance,created_at', 'messages.sender:id,name']);

        return Inertia::render('Admin/Support/Show', [
            'conversation' => $conversation,
        ]);
    }

    public function reply(Request $request, SupportConversation $conversation, SupportTicketService $service): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $service->replyAsAdmin($conversation, $request->user(), $validated['body']);

        return back();
    }

    public function updateStatus(Request $request, SupportConversation $conversation, SupportTicketService $service): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:awaiting_admin,awaiting_user,resolved,closed'],
        ]);

        $service->updateStatus($conversation, $validated['status']);

        return back();
    }
}
