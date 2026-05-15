<?php

namespace App\Http\Controllers;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailCampaignAggregatedDataExportController extends Controller
{
    public function __invoke(MailCampaign $mailCampaign): StreamedResponse
    {
        $campaignName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $mailCampaign->name ?? 'campaign');
        $filename = sprintf('du-lieu-aggregate_%s_%s.csv', $campaignName, now()->format('Ymd_His'));

        $recipients = MailCampaignRecipient::query()
            ->where('mail_campaign_id', $mailCampaign->id)
            ->with(['aggregatedRecord'])
            ->orderBy('customer_code')
            ->get();

        return response()->streamDownload(function () use ($recipients): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            // Row 1: group headers (merged cell simulation via empty cols)
            fputcsv($handle, [
                '', '',
                'Sheet Tổng hợp', '', '', '', '',
                'Sheet Khoán NPP',
                'Sheet Cám cá', '', '', '', '',
                'Sheet Key Account', '', '', '', '',
            ]);

            // Row 2: column headers
            fputcsv($handle, [
                'Mã số khách hàng',
                'Email',
                'Tổng sản lượng (gồm cám thủy sản)',
                'Doanh thu (gồm cám thủy sản)',
                'Tiền chiết khấu theo Hóa đơn',
                'Chiết khấu khác ( Không thể hiện trên hóa đơn)',
                'Tổng cộng',
                'Tổng cộng',
                'Tổng sản lượng',
                'Doanh thu',
                'Tiền chiết khấu theo Hóa đơn',
                'Chiết khấu khác ( Không thể hiện trên hóa đơn)',
                'Tổng cộng',
                'Tổng sản lượng',
                'Doanh thu',
                'Chiết khấu theo hóa đơn',
                'Chiết khấu khác ( Không thể hiện trên hóa đơn)',
                'Tổng cộng',
            ]);

            foreach ($recipients as $recipient) {
                $payload = $recipient->aggregatedRecord?->aggregated_payload ?? [];
                $payload = is_array($payload) ? $payload : [];

                $th  = is_array($payload['tongHop'] ?? null)    ? $payload['tongHop']    : [];
                $npp = is_array($payload['khoanNpp'] ?? null)   ? $payload['khoanNpp']   : [];
                $cc  = is_array($payload['camCa'] ?? null)      ? $payload['camCa']      : [];
                $ka  = is_array($payload['keyAccount'] ?? null) ? $payload['keyAccount'] : [];

                // Key Account: "Chiết khấu khác" = sum of discreteItems values
                $kaOtherDiscount = '';
                if ($ka !== []) {
                    $sum = 0.0;
                    $hasValue = false;
                    foreach (($ka['discreteItems'] ?? []) as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        $raw = trim((string) ($item['value'] ?? ''));
                        if ($raw === '') {
                            continue;
                        }
                        $numeric = (float) str_replace([',', ' '], '', $raw);
                        $sum += $numeric;
                        $hasValue = true;
                    }
                    $kaOtherDiscount = $hasValue ? number_format($sum, 0, '.', ',') : '';
                }

                fputcsv($handle, [
                    $recipient->customer_code,
                    $recipient->recipient_email ?? '',
                    // TongHop
                    (string) ($th['totalQuantity'] ?? ''),
                    (string) ($th['revenue'] ?? ''),
                    (string) ($th['invoiceDiscount'] ?? ''),
                    (string) ($th['otherDiscount'] ?? ''),
                    (string) ($th['grandTotal'] ?? ''),
                    // KhoanNpp
                    (string) ($npp['grandTotal'] ?? ''),
                    // CamCa
                    (string) ($cc['totalQuantity'] ?? ''),
                    (string) ($cc['revenue'] ?? ''),
                    (string) ($cc['invoiceDiscount'] ?? ''),
                    (string) ($cc['otherDiscount'] ?? ''),
                    (string) ($cc['grandTotal'] ?? ''),
                    // KeyAccount
                    (string) ($ka['totalQuantity'] ?? ''),
                    (string) ($ka['revenue'] ?? ''),
                    (string) ($ka['invoiceDiscount'] ?? ''),
                    $kaOtherDiscount,
                    (string) ($ka['grandTotal'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
