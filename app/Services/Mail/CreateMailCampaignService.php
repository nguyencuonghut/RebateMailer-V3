<?php

namespace App\Services\Mail;

use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateMailCampaignService
{
    public function __construct(
        private readonly EnsureMailCampaignTemplateMonthMatchesBatchService $ensureMailCampaignTemplateMonthMatchesBatchService,
    ) {
    }

    /**
     * @param  array{name:string,import_batch_id:int,mail_template_canvas_id:int,notes?:string|null}  $input
     */
    public function create(User $user, array $input): MailCampaign
    {
        return DB::transaction(function () use ($user, $input): MailCampaign {
            $importBatch = ImportBatch::query()->findOrFail($input['import_batch_id']);
            $templateCanvas = MailTemplateCanvas::query()->findOrFail($input['mail_template_canvas_id']);

            $this->ensureMailCampaignTemplateMonthMatchesBatchService->assertForCreation($importBatch, $templateCanvas);

            $campaign = MailCampaign::query()->create([
                'name' => $input['name'],
                'import_batch_id' => $importBatch->id,
                'mail_template_canvas_id' => $templateCanvas->id,
                'notes' => $input['notes'] ?? null,
                'status' => 'draft',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $importBatch->aggregatedRecords()
                ->orderBy('customer_code')
                ->get()
                ->each(function (ImportBatchAggregatedRecord $record) use ($campaign): void {
                    $payload = is_array($record->aggregated_payload) ? $record->aggregated_payload : [];
                    $recipientEmails = $this->resolveRecipientEmails($payload);

                    foreach ($recipientEmails as $recipientEmail) {
                        MailCampaignRecipient::query()->create([
                            'mail_campaign_id' => $campaign->id,
                            'import_batch_aggregated_record_id' => $record->id,
                            'customer_code' => (string) ($payload['customerCode'] ?? $record->customer_code),
                            'customer_full_name' => trim((string) ($payload['customerFullName'] ?? $record->customer_code)),
                            'customer_type' => (string) ($payload['customerType'] ?? $record->customer_type),
                            'recipient_email' => $recipientEmail,
                            'delivery_status' => 'pending',
                            'attempts_count' => 0,
                        ]);
                    }
                });

            return $campaign;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string|null>
     */
    private function resolveRecipientEmails(array $payload): array
    {
        $candidateLists = [
            $payload['emails'] ?? null,
            data_get($payload, 'tongHop.emails'),
            data_get($payload, 'khoanNpp.emails'),
            data_get($payload, 'camCa.emails'),
            data_get($payload, 'keyAccount.emails'),
        ];
        $resolved = [];
        $seen = [];

        foreach ($candidateLists as $candidateList) {
            if (! is_array($candidateList)) {
                continue;
            }

            foreach ($candidateList as $candidate) {
                $email = trim((string) $candidate);

                if ($email === '') {
                    continue;
                }

                $normalizedKey = mb_strtolower($email);

                if (isset($seen[$normalizedKey])) {
                    continue;
                }

                $seen[$normalizedKey] = true;
                $resolved[] = $email;
            }
        }

        if ($resolved !== []) {
            return $resolved;
        }

        $fallbackCandidates = [
            $payload['email'] ?? null,
            data_get($payload, 'tongHop.email'),
            data_get($payload, 'khoanNpp.email'),
            data_get($payload, 'camCa.email'),
            data_get($payload, 'keyAccount.email'),
        ];

        foreach ($fallbackCandidates as $candidate) {
            $email = trim((string) $candidate);

            if ($email !== '') {
                return [$email];
            }
        }

        return [null];
    }
}
