<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailCampaignPdfExportDownloadController extends Controller
{
    public function __invoke(MailCampaign $mailCampaign, MailCampaignExport $mailCampaignExport): StreamedResponse|Response
    {
        abort_unless($mailCampaignExport->mail_campaign_id === $mailCampaign->id, 404);
        abort_unless($mailCampaignExport->export_type === 'pdf', 404);
        abort_unless($mailCampaignExport->status === 'completed', 404);

        $disk = $mailCampaignExport->file_disk ?: (string) config('mail_campaigns.exports.disk', 'local');
        $path = (string) $mailCampaignExport->file_path;

        abort_unless($path !== '' && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download(
            $path,
            $mailCampaignExport->file_name ?: basename($path),
        );
    }
}
