<?php

namespace App\Http\Controllers;

use App\Services\Templates\TemplatePageService;
use App\Support\Authorization\PermissionName;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplatePageController extends Controller
{
    public function __construct(
        private readonly TemplatePageService $templatePageService,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render(
            'Templates/Index',
            $this->templatePageService->getIndexPageData(
                $request->user()?->can(PermissionName::TemplatesManage->value) ?? false,
                $request->integer('subject_preview_record') ?: null,
                $request->integer('greeting_preview_record') ?: null,
                $request->integer('tong_hop_preview_record') ?: null,
                $request->integer('khoan_npp_preview_record') ?: null,
                $request->integer('cam_ca_preview_record') ?: null,
            ),
        );
    }
}
