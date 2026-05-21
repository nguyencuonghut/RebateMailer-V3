<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Services\Mail\BuildMailCampaignRecipientSnapshotPdfService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class MailCampaignRecipientPdfDownloadController extends Controller
{
    public function __invoke(
        MailCampaign $mailCampaign,
        MailCampaignRecipient $mailCampaignRecipient,
        BuildMailCampaignRecipientSnapshotPdfService $buildMailCampaignRecipientSnapshotPdfService,
    ): Response|RedirectResponse {
        if ($mailCampaignRecipient->mail_campaign_id !== $mailCampaign->id) {
            abort(404);
        }

        try {
            $pdfBinary = $buildMailCampaignRecipientSnapshotPdfService->build(
                $mailCampaignRecipient,
                $mailCampaign->name,
            );
        } catch (\RuntimeException) {
            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->withErrors([
                    'download' => 'Mail này chưa có đủ snapshot để trích xuất PDF.',
                ]);
        }

        $fileName = sprintf(
            'mail-%s-%s.pdf',
            $mailCampaignRecipient->customer_code ?: $mailCampaignRecipient->id,
            now()->format('Ymd_His'),
        );

        return response()->streamDownload(
            static function () use ($pdfBinary): void {
                echo $pdfBinary;
            },
            $fileName,
            [
                'Content-Type' => 'application/pdf',
            ],
        );
    }
}
