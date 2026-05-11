<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailCampaignFailedRecipientsExportController extends Controller
{
    public function __invoke(MailCampaign $mailCampaign): StreamedResponse
    {
        $campaignName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $mailCampaign->name ?? 'campaign');
        $filename = sprintf('loi-gui-mail_%s_%s.csv', $campaignName, now()->format('Ymd_His'));

        $recipients = MailCampaignRecipient::query()
            ->where('mail_campaign_id', $mailCampaign->id)
            ->where('delivery_status', 'failed')
            ->with(['aggregatedRecord'])
            ->orderBy('customer_code')
            ->get();

        return response()->streamDownload(function () use ($recipients): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel Vietnamese character support
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'STT',
                'Mã số',
                'Tên khách hàng',
                'Email',
                'Nguồn dữ liệu',
                'Số lần thử',
                'Lỗi gần nhất',
                'Thời gian lỗi',
            ]);

            foreach ($recipients as $index => $recipient) {
                $sourceSheets = $recipient->aggregatedRecord?->source_sheets ?? [];
                $sourceSheetsLabel = is_array($sourceSheets) && $sourceSheets !== []
                    ? implode(' | ', array_map(static fn ($v): string => (string) $v, $sourceSheets))
                    : 'Không xác định';

                fputcsv($handle, [
                    $index + 1,
                    $recipient->customer_code,
                    $recipient->customer_full_name,
                    $recipient->recipient_email ?? 'Chưa có email',
                    $sourceSheetsLabel,
                    $recipient->attempts_count,
                    $recipient->latest_error_message ?? '',
                    optional($recipient->failed_at)->format('d/m/Y H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
