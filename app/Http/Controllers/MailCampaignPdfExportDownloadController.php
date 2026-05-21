<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailCampaignPdfExportDownloadController extends Controller
{
    public function __invoke(MailCampaign $mailCampaign, MailCampaignExport $mailCampaignExport): StreamedResponse|RedirectResponse
    {
        if ($mailCampaignExport->mail_campaign_id !== $mailCampaign->id) {
            abort(404);
        }

        $disk = (string) config('mail_campaigns.exports.disk', 'local');

        if (
            $mailCampaignExport->status !== 'completed'
            || ! filled($mailCampaignExport->file_path)
            || ! Storage::disk($disk)->exists((string) $mailCampaignExport->file_path)
        ) {
            return redirect()
                ->route('mail.index', ['campaign' => $mailCampaign->id])
                ->withErrors([
                    'download' => 'File export PDF chưa sẵn sàng để tải xuống.',
                ]);
        }

        return Storage::disk($disk)->download(
            (string) $mailCampaignExport->file_path,
            $mailCampaignExport->file_name ?: 'mail-campaign-export.pdf',
        );
    }
}
