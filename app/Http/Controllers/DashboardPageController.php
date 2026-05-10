<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardPageController extends Controller
{
    public function __construct(
        private readonly DashboardPageService $dashboardPageService,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return Inertia::render(
            'Dashboard',
            $this->dashboardPageService->getPageData($request->user()),
        );
    }
}
