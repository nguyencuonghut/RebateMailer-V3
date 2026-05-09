<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Services\Mail\RetryMailCampaignRecipientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailCampaignRecipientRetryController extends Controller
{
    public function __construct(
        private readonly RetryMailCampaignRecipientService $retryMailCampaignRecipientService,
    ) {
    }

    public function __invoke(Request $request, MailCampaign $mailCampaign, MailCampaignRecipient $mailCampaignRecipient): RedirectResponse
    {
        abort_unless($request->user()?->can('mail.send'), 403);

        $this->retryMailCampaignRecipientService->retry($mailCampaign, $mailCampaignRecipient, $request->user());

        return redirect()->route('mail.index', ['campaign' => $mailCampaign->id]);
    }
}
