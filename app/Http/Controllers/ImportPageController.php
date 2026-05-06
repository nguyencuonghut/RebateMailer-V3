<?php

namespace App\Http\Controllers;

use App\Services\Imports\ImportPageService;
use App\Support\Authorization\PermissionName;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportPageController extends Controller
{
    public function __construct(
        private readonly ImportPageService $importPageService,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render(
            'Imports/Index',
            $this->importPageService->getIndexPageData(
                $request->user()?->can(PermissionName::ImportsManage->value) ?? false,
                $request->integer('batch') ?: null,
            ),
        );
    }
}
