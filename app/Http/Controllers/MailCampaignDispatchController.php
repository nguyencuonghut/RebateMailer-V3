<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Services\Mail\StartMailCampaignDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailCampaignDispatchController extends Controller
{
    public function __construct(
        private readonly StartMailCampaignDispatchService $startMailCampaignDispatchService,
    ) {
    }

    public function __invoke(Request $request, MailCampaign $mailCampaign): RedirectResponse
    {
        abort_unless($request->user()?->can('mail.send'), 403);

        $this->startMailCampaignDispatchService->start($mailCampaign, $request->user());

        return redirect()->route('mail.index', ['campaign' => $mailCampaign->id]);
    }
}
