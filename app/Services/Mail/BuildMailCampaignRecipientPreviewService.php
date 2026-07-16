<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplate;
use App\Services\Templates\BuildTemplateCamCaTablePreviewService;
use App\Services\Templates\BuildTemplateGreetingPreviewService;
use App\Services\Templates\BuildTemplateKeyAccountTablePreviewService;
use App\Services\Templates\BuildTemplateKhoanNppTablePreviewService;
use App\Services\Templates\BuildTemplatePreviewSampleService;
use App\Services\Templates\BuildTemplateSubjectPreviewService;
use App\Services\Templates\BuildTemplateTongHopTablePreviewService;

class BuildMailCampaignRecipientPreviewService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientEmailHtmlService $buildMailCampaignRecipientEmailHtmlService,
        private readonly BuildTemplateSubjectPreviewService $buildTemplateSubjectPreviewService,
        private readonly BuildTemplateGreetingPreviewService $buildTemplateGreetingPreviewService,
        private readonly BuildTemplateTongHopTablePreviewService $buildTemplateTongHopTablePreviewService,
        private readonly BuildTemplateKhoanNppTablePreviewService $buildTemplateKhoanNppTablePreviewService,
        private readonly BuildTemplateCamCaTablePreviewService $buildTemplateCamCaTablePreviewService,
        private readonly BuildTemplateKeyAccountTablePreviewService $buildTemplateKeyAccountTablePreviewService,
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(MailCampaign $campaign, ?int $recipientId = null, bool $includeHtml = true): ?array
    {
        if ($recipientId === null) {
            return null;
        }

        $recipient = $campaign->recipients()
            ->with('aggregatedRecord')
            ->find($recipientId);

        if (! $recipient) {
            return null;
        }

        return $this->buildForRecipient($campaign, $recipient, $includeHtml);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buildForRecipient(MailCampaign $campaign, MailCampaignRecipient $recipient, bool $includeHtml = true): ?array
    {
        if ((int) $recipient->mail_campaign_id !== (int) $campaign->getKey()) {
            return null;
        }

        $recipient->loadMissing('aggregatedRecord');
        $campaign->loadMissing('templateCanvas.legacyMailTemplate');

        if ($recipient->aggregatedRecord) {
            $this->buildTemplatePreviewSampleService->rememberSelectedRecord($recipient->aggregatedRecord);
        }

        $mailTemplate = $campaign->templateCanvas?->legacyMailTemplate;

        if (! $mailTemplate instanceof MailTemplate) {
            $preview = [
                'recipient' => [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'customerType' => $recipient->customer_type,
                ],
                'subject' => null,
                'greeting' => null,
                'tables' => [],
                'errors' => ['Template canvas hiện chưa có liên kết legacy mail template để dựng preview đầy đủ.'],
            ];

            if ($includeHtml) {
                $preview['html'] = $this->buildMailCampaignRecipientEmailHtmlService->build($preview, $campaign->name);
            }

            return $preview;
        }

        $batchId = $campaign->import_batch_id;
        $aggregatedRecordId = $recipient->import_batch_aggregated_record_id;

        $tables = array_values(array_filter([
            $this->normalizeTablePreview('tong-hop-table', 'Bảng chế độ tháng', $this->buildTemplateTongHopTablePreviewService->build($mailTemplate, $batchId, $aggregatedRecordId)),
            $this->normalizeTablePreview('khoan-npp-table', 'Bảng chương trình khoán đặc biệt', $this->buildTemplateKhoanNppTablePreviewService->build($mailTemplate, $batchId, $aggregatedRecordId)),
            $this->normalizeTablePreview('cam-ca-table', 'Bảng chiết khấu cám cá', $this->buildTemplateCamCaTablePreviewService->build($mailTemplate, $batchId, $aggregatedRecordId)),
            $this->normalizeTablePreview('key-account-table', 'Bảng chiết khấu Key Account', $this->buildTemplateKeyAccountTablePreviewService->build($mailTemplate, $batchId, $aggregatedRecordId)),
        ]));

        $preview = [
            'recipient' => [
                'id' => $recipient->id,
                'customerCode' => $recipient->customer_code,
                'customerFullName' => $recipient->customer_full_name,
                'recipientEmail' => $recipient->recipient_email,
                'customerType' => $recipient->customer_type,
            ],
            'subject' => $this->buildTemplateSubjectPreviewService->build($mailTemplate, $batchId, $aggregatedRecordId),
            'greeting' => $this->buildTemplateGreetingPreviewService->build($mailTemplate, $batchId, $aggregatedRecordId),
            'tables' => $tables,
            'errors' => [],
        ];

        if ($includeHtml) {
            $preview['html'] = $this->buildMailCampaignRecipientEmailHtmlService->build($preview, $campaign->name);
        }

        return $preview;
    }

    /**
     * @param  array<string, mixed>|null  $preview
     * @return array<string, mixed>|null
     */
    private function normalizeTablePreview(string $type, string $label, ?array $preview): ?array
    {
        if ($preview === null) {
            return null;
        }

        return [
            'type' => $type,
            'label' => $label,
            'title' => $preview['title'] ?? $label,
            'sourceSheet' => $preview['sourceSheet'] ?? null,
            'rows' => $preview['rows'] ?? [],
            'errors' => $preview['errors'] ?? [],
        ];
    }
}
