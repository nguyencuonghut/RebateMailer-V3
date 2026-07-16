<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Services\Mail\StartMailCampaignDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MailCampaignDispatchController extends Controller
{
    public function __construct(
        private readonly StartMailCampaignDispatchService $startMailCampaignDispatchService,
    ) {
    }

    public function __invoke(Request $request, MailCampaign $mailCampaign): RedirectResponse
    {
        abort_unless($request->user()?->can('mail.send'), 403);

        try {
            $this->startMailCampaignDispatchService->start($mailCampaign, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->withErrors(['dispatch' => $exception->getMessage()]);
        }

        return redirect()->route('mail.index', ['campaign' => $mailCampaign->id]);
    }
}
