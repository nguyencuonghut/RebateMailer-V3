<?php

namespace App\Services\Mail;

use App\Mail\MailCampaignRecipientMail;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSampleMailCampaignService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPreviewService $buildMailCampaignRecipientPreviewService,
        private readonly BuildMailCampaignRecipientEmailHtmlService $buildMailCampaignRecipientEmailHtmlService,
    ) {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function send(MailCampaign $campaign, string $targetEmail): array
    {
        $campaign->loadMissing(['recipients.aggregatedRecord']);

        $samples = $this->selectSampleRecipients($campaign);

        if ($samples === []) {
            return [[
                'label' => 'Tất cả loại',
                'customerCode' => '',
                'customerFullName' => '',
                'status' => 'skipped',
                'message' => 'Chiến dịch không có người nhận nào để gửi mẫu.',
            ]];
        }

        $results = [];

        foreach ($samples as $item) {
            ['label' => $label, 'recipient' => $recipient] = $item;

            $preview = $this->buildMailCampaignRecipientPreviewService->build($campaign, $recipient->id);

            if (! is_array($preview)) {
                $results[] = [
                    'label' => $label,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'status' => 'error',
                    'message' => 'Không dựng được nội dung email mẫu.',
                ];
                continue;
            }

            $renderErrors = array_values(array_filter([
                ...($preview['errors'] ?? []),
                ...($preview['subject']['errors'] ?? []),
            ], static fn ($v): bool => is_string($v) && $v !== ''));

            $subjectLine = $preview['subject']['renderedText'] ?? $campaign->name;
            $html = $this->buildMailCampaignRecipientEmailHtmlService->build($preview, isPreview: false);

            try {
                Mail::to($targetEmail)->send(new MailCampaignRecipientMail($subjectLine, $html));

                $successNote = $renderErrors !== []
                    ? sprintf('Đã gửi (có cảnh báo: %s)', $renderErrors[0])
                    : 'Đã gửi thành công.';

                $results[] = [
                    'label' => $label,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'status' => 'sent',
                    'message' => $successNote,
                ];
            } catch (Throwable $throwable) {
                $results[] = [
                    'label' => $label,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'status' => 'error',
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{label: string, recipient: MailCampaignRecipient}>
     */
    private function selectSampleRecipients(MailCampaign $campaign): array
    {
        $tongHopKhoanNpp = null;
        $camCa = null;
        $keyAccount = null;

        foreach ($campaign->recipients as $recipient) {
            $sheets = $recipient->aggregatedRecord?->source_sheets ?? [];

            if (! is_array($sheets)) {
                continue;
            }

            $hasTongHop = in_array('Tổng hợp', $sheets, true);
            $hasKhoanNpp = in_array('Khoán NPP', $sheets, true);
            $hasCamCa = in_array('Cám cá', $sheets, true);
            $hasKeyAccount = in_array('Key Account', $sheets, true);

            // Priority: prefer customer with BOTH Tổng hợp + Khoán NPP for richest template
            if ($tongHopKhoanNpp === null && ($hasTongHop || $hasKhoanNpp) && ! $hasCamCa && ! $hasKeyAccount) {
                $tongHopKhoanNpp = $recipient;
            }

            if ($camCa === null && $hasCamCa) {
                $camCa = $recipient;
            }

            if ($keyAccount === null && $hasKeyAccount) {
                $keyAccount = $recipient;
            }

            if ($tongHopKhoanNpp !== null && $camCa !== null && $keyAccount !== null) {
                break;
            }
        }

        // Second pass: prefer customer with BOTH sheets if first pass only found one-sheet customer
        if ($tongHopKhoanNpp !== null) {
            $sheets = $tongHopKhoanNpp->aggregatedRecord?->source_sheets ?? [];
            $hasBoth = in_array('Tổng hợp', $sheets, true) && in_array('Khoán NPP', $sheets, true);

            if (! $hasBoth) {
                foreach ($campaign->recipients as $recipient) {
                    $s = $recipient->aggregatedRecord?->source_sheets ?? [];
                    if (
                        is_array($s)
                        && in_array('Tổng hợp', $s, true)
                        && in_array('Khoán NPP', $s, true)
                        && ! in_array('Cám cá', $s, true)
                        && ! in_array('Key Account', $s, true)
                    ) {
                        $tongHopKhoanNpp = $recipient;
                        break;
                    }
                }
            }
        }

        $results = [];

        if ($tongHopKhoanNpp !== null) {
            $results[] = ['label' => 'Tổng hợp / Khoán NPP', 'recipient' => $tongHopKhoanNpp];
        }

        if ($camCa !== null) {
            $results[] = ['label' => 'Cám cá', 'recipient' => $camCa];
        }

        if ($keyAccount !== null) {
            $results[] = ['label' => 'Key Account', 'recipient' => $keyAccount];
        }

        return $results;
    }
}
