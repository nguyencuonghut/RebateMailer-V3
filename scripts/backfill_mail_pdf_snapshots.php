#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Models\MailCampaignRecipient;
use App\Services\Mail\BuildMailCampaignRecipientPdfPayloadService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = getopt('', [
    'write',
    'campaign-id::',
    'chunk::',
    'help',
]);

if (array_key_exists('help', $options)) {
    fwrite(STDOUT, <<<TEXT
Usage:
  php scripts/backfill_mail_pdf_snapshots.php [--campaign-id=123] [--chunk=100] [--write]

Default mode:
  Dry run only. The script validates whether snapshot payloads can be rebuilt.

Options:
  --write           Persist snapshots to database. Without this flag, nothing is written.
  --campaign-id     Limit processing to a single mail_campaign_id.
  --chunk           Chunk size for chunkById. Default: 100.
  --help            Show this help message.

Examples:
  php scripts/backfill_mail_pdf_snapshots.php
  php scripts/backfill_mail_pdf_snapshots.php --campaign-id=12
  php scripts/backfill_mail_pdf_snapshots.php --write
  php scripts/backfill_mail_pdf_snapshots.php --campaign-id=12 --write

TEXT);

    exit(0);
}

$write = array_key_exists('write', $options);
$campaignId = isset($options['campaign-id']) && $options['campaign-id'] !== false
    ? (int) $options['campaign-id']
    : null;
$chunkSize = isset($options['chunk']) && $options['chunk'] !== false
    ? max(1, (int) $options['chunk'])
    : 100;

/** @var BuildMailCampaignRecipientPdfPayloadService $service */
$service = app(BuildMailCampaignRecipientPdfPayloadService::class);

$query = MailCampaignRecipient::query()
    ->with('campaign')
    ->where('delivery_status', 'sent')
    ->whereNull('sent_subject_snapshot')
    ->orderBy('id');

if ($campaignId !== null) {
    $query->where('mail_campaign_id', $campaignId);
}

$modeLabel = $write ? 'WRITE' : 'DRY RUN';
fwrite(STDOUT, sprintf(
    "[%s] Start backfill snapshots | campaign_id=%s | chunk=%d\n",
    $modeLabel,
    $campaignId !== null ? (string) $campaignId : 'ALL',
    $chunkSize,
));

$processed = 0;
$updated = 0;
$skipped = 0;

$query->chunkById($chunkSize, function ($recipients) use ($service, $write, &$processed, &$updated, &$skipped): void {
    foreach ($recipients as $recipient) {
        $processed++;

        try {
            $campaign = $recipient->campaign;

            if (! $campaign) {
                throw new RuntimeException('Missing campaign');
            }

            $payload = $service->build($campaign, $recipient);

            $subject = trim((string) ($payload['subjectLine'] ?? ''));
            $html = filled($payload['bodyHtml'] ?? null)
                ? (string) $payload['bodyHtml']
                : (string) data_get($payload, 'preview.html', '');
            $signature = $payload['signature'] ?? null;

            if ($subject === '') {
                throw new RuntimeException('Invalid payload: empty subject');
            }

            if (trim($html) === '') {
                throw new RuntimeException('Invalid payload: empty html');
            }

            if (! is_array($signature)) {
                throw new RuntimeException('Invalid payload: signature snapshot missing');
            }

            if ($write) {
                DB::transaction(function () use ($recipient, $subject, $html, $signature): void {
                    $recipient->forceFill([
                        'sent_subject_snapshot' => $subject,
                        'sent_html_snapshot' => $html,
                        'sent_signature_snapshot' => $signature,
                        'snapshot_version' => 1,
                    ])->save();
                });
            }

            $updated++;
        } catch (Throwable $throwable) {
            $skipped++;

            fwrite(STDOUT, sprintf(
                "SKIP recipient_id=%d campaign_id=%d reason=%s\n",
                $recipient->id,
                $recipient->mail_campaign_id,
                $throwable->getMessage(),
            ));
        }
    }
});

fwrite(STDOUT, sprintf(
    "[%s] DONE processed=%d updated=%d skipped=%d\n",
    $modeLabel,
    $processed,
    $updated,
    $skipped,
));
