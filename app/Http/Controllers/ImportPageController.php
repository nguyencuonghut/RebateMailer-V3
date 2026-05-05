<?php

namespace App\Http\Controllers;

use App\Services\Imports\ImportPageService;
use Inertia\Inertia;
use Inertia\Response;

class ImportPageController extends Controller
{
    public function __construct(
        private readonly ImportPageService $importPageService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Imports/Index', $this->importPageService->getIndexPageData());
    }
}
