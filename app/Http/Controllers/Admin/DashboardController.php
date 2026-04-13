<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the admin dashboard with platform-wide statistics.
 */
class DashboardController extends Controller
{
    public function __invoke(AdminStatsService $stats): Response
    {
        return Inertia::render('Admin/Dashboard', $stats->dashboard());
    }
}
