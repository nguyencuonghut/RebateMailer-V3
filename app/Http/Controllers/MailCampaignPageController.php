<?php

namespace App\Http\Controllers;

use App\Services\Mail\MailCampaignPageService;
use App\Support\Authorization\PermissionName;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailCampaignPageController extends Controller
{
    public function __construct(
        private readonly MailCampaignPageService $mailCampaignPageService,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render(
            'Mail/Index',
            $this->mailCampaignPageService->getIndexPageData(
                $request->user()?->can(PermissionName::MailSend->value) ?? false,
                $request->integer('campaign') ?: null,
                $request->integer('recipient') ?: null,
            ),
        );
    }
}
