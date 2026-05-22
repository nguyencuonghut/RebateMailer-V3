<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use RuntimeException;

class BuildMailCampaignRecipientPdfPayloadService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPreviewService $buildMailCampaignRecipientPreviewService,
        private readonly BuildRepresentativeSignatureSnapshotService $buildRepresentativeSignatureSnapshotService,
        private readonly ExtractMailCampaignRecipientBodyHtmlService $extractMailCampaignRecipientBodyHtmlService,
    ) {
    }

    /**
     * @return array{subjectLine: string, bodyHtml: string|null, preview: array<string, mixed>|null, signature: array<string, string|null>}
     */
    public function build(MailCampaign $campaign, MailCampaignRecipient $recipient): array
    {
        $hasSnapshot = filled($recipient->sent_subject_snapshot)
            && filled($recipient->sent_html_snapshot)
            && is_array($recipient->sent_signature_snapshot);

        if ($hasSnapshot) {
            return [
                'subjectLine' => (string) $recipient->sent_subject_snapshot,
                'bodyHtml' => $this->extractMailCampaignRecipientBodyHtmlService->extract((string) $recipient->sent_html_snapshot),
                'preview' => null,
                'signature' => $recipient->sent_signature_snapshot,
            ];
        }

        $preview = $this->buildMailCampaignRecipientPreviewService->build($campaign, $recipient->id);

        if (! is_array($preview)) {
            throw new RuntimeException(sprintf(
                'Không dựng được preview để export PDF cho khách hàng %s.',
                $recipient->customer_code,
            ));
        }

        $errors = array_values(array_filter([
            ...($preview['errors'] ?? []),
            ...($preview['subject']['errors'] ?? []),
            ...($preview['greeting']['errors'] ?? []),
            ...collect($preview['tables'] ?? [])->flatMap(fn (array $table): array => array_values($table['errors'] ?? []))->all(),
        ], static fn (mixed $message): bool => is_string($message) && $message !== ''));

        if ($errors !== []) {
            throw new RuntimeException($errors[0]);
        }

        return [
            'subjectLine' => (string) data_get($preview, 'subject.renderedText', $campaign->name),
            'bodyHtml' => null,
            'preview' => $preview,
            'signature' => $this->buildRepresentativeSignatureSnapshotService->build($campaign, $recipient),
        ];
    }
}
