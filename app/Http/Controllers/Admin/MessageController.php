<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin messaging — send broadcast or direct messages with optional push.
 */
class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AdminMessage::query()
            ->with(['sender:id,name', 'recipient:id,uuid,name,email'])
            ->latest();

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('body', 'ilike', "%{$search}%")
                    ->orWhereHas('recipient', function ($uq) use ($search) {
                        $uq->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
            });
        }

        $messages = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $messages,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request, MessagingService $messaging): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'recipient_uuid' => ['nullable', 'string', 'exists:users,uuid'],
            'send_push' => ['boolean'],
        ]);

        $recipientId = null;
        if (! empty($validated['recipient_uuid'])) {
            $recipientId = User::where('uuid', $validated['recipient_uuid'])->value('id');
        }

        $messaging->send(
            $request->user(),
            $recipientId,
            $validated['title'],
            $validated['body'],
            $validated['send_push'] ?? true,
        );

        return back()->with('flash', ['success' => 'Message sent']);
    }
}
