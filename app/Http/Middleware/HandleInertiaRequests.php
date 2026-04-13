<?php

namespace App\Http\Middleware;

use App\Services\MessagingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'flash' => fn() => $request->session()->get('flash'),
            'auth' => [
                'user' => $user ? [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'token_balance' => $user->token_balance,
                    'is_admin' => (bool) $user->is_admin,
                ] : null,
                'impersonating' => $request->session()->has('impersonator_id'),
                'unread_count' => fn () => $user
                    ? app(MessagingService::class)->totalUnreadForUser($user)
                    : 0,
                'support_unread' => fn () => $user
                    ? app(MessagingService::class)->userSupportUnreadCount($user)
                    : 0,
                'admin_support_inbox' => fn () => $user?->is_admin
                    ? app(MessagingService::class)->adminSupportInboxCount()
                    : 0,
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
