<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Services\Mail\BuildMailCampaignRecipientPdfDownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use RuntimeException;

class MailCampaignRecipientPdfDownloadController extends Controller
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPdfDownloadService $buildMailCampaignRecipientPdfDownloadService,
    ) {
    }

    public function __invoke(MailCampaign $mailCampaign, MailCampaignRecipient $mailCampaignRecipient): Response|RedirectResponse
    {
        abort_unless($mailCampaignRecipient->mail_campaign_id === $mailCampaign->id, 404);

        try {
            $result = $this->buildMailCampaignRecipientPdfDownloadService->build(
                $mailCampaign,
                $mailCampaignRecipient,
            );

            return response($result['binary'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename='.$result['fileName'],
            ]);
        } catch (RuntimeException $runtimeException) {
            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->with('error', $runtimeException->getMessage());
        }
    }
}
