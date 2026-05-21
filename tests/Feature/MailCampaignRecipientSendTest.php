<?php

namespace Tests\Feature;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Mail\MailCampaignRecipientMail;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Models\MailTemplateCanvasPart;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Mail\BuildMailCampaignRecipientEmailHtmlService;
use App\Services\Mail\BuildMailCampaignRecipientPreviewService;
use App\Services\Mail\BuildRepresentativeSignatureSnapshotService;
use App\Services\Mail\LogMailCampaignRecipientAttemptService;
use App\Services\Mail\UpdateMailCampaignDispatchStatusService;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class MailCampaignRecipientSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dispatch_job_sends_mail_and_marks_campaign_completed(): void
    {
        Mail::fake();
        $capturedSubjectLine = null;
        $capturedHtmlBody = null;

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $this->bindRepresentativeSignatureToCanvas($campaign->templateCanvas, $user->id, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                'representativeRole' => 'Giám đốc kinh doanh',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5LXNpZw==',
                'representativeRole' => 'Giám đốc Key Account',
                'representativeName' => 'Trần Thị B',
            ],
        ]);

        $previewService = Mockery::mock(BuildMailCampaignRecipientPreviewService::class);
        $previewService->shouldReceive('build')
            ->once()
            ->withArgs(fn (MailCampaign $resolvedCampaign, int $recipientId): bool => $resolvedCampaign->id === $campaign->id && $recipientId === $recipient->id)
            ->andReturn([
                'recipient' => [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'customerType' => $recipient->customer_type,
                ],
                'subject' => [
                    'renderedText' => 'Thư chiết khấu tháng 6',
                    'errors' => [],
                ],
                'greeting' => [
                    'renderedText' => 'Kính gửi Quý khách',
                    'errors' => [],
                ],
                'tables' => [],
                'errors' => [],
                'html' => '<html><body><h1>Preview mail</h1></body></html>',
            ]);
        app()->instance(BuildMailCampaignRecipientPreviewService::class, $previewService);

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        Mail::assertSent(MailCampaignRecipientMail::class, function (MailCampaignRecipientMail $mail) use ($recipient, &$capturedSubjectLine, &$capturedHtmlBody): bool {
            $capturedSubjectLine = $mail->subjectLine;
            $capturedHtmlBody = $mail->htmlBody;

            return $mail->hasTo((string) $recipient->recipient_email)
                && $mail->subjectLine === 'Thư chiết khấu tháng 6';
        });

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'latest_error_message' => null,
        ]);

        $recipient->refresh();

        $this->assertSame('Thư chiết khấu tháng 6', $capturedSubjectLine);
        $this->assertIsString($capturedHtmlBody);
        $this->assertSame($capturedSubjectLine, $recipient->sent_subject_snapshot);
        $this->assertSame($capturedHtmlBody, $recipient->sent_html_snapshot);
        $this->assertSame(1, $recipient->snapshot_version);
        $this->assertSame([
            'partType' => 'representative-signature',
            'customerType' => 'Khách thường',
            'title' => 'Đại diện công ty',
            'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
            'representativeRole' => 'Giám đốc kinh doanh',
            'representativeName' => 'Nguyễn Văn A',
        ], $recipient->sent_signature_snapshot);

        $this->assertDatabaseHas('mail_campaign_recipient_attempts', [
            'mail_campaign_recipient_id' => $recipient->id,
            'event_type' => 'sent',
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'completed',
        ]);
    }

    public function test_dispatch_job_marks_campaign_completed_with_failures_when_other_recipient_failed(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $this->bindRepresentativeSignatureToCanvas($campaign->templateCanvas, $user->id, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                'representativeRole' => 'Giám đốc kinh doanh',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5LXNpZw==',
                'representativeRole' => 'Giám đốc Key Account',
                'representativeName' => 'Trần Thị B',
            ],
        ]);

        MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => ImportBatchAggregatedRecord::query()->create([
                'import_batch_id' => $campaign->import_batch_id,
                'customer_code' => '90301',
                'customer_type' => 'Khách thường',
                'source_sheets' => ['Tổng hợp'],
                'aggregated_payload' => [
                    'customerCode' => '90301',
                    'customerFullName' => '90301 - Công ty B',
                    'customerType' => 'Khách thường',
                    'tongHop' => [
                        'email' => '',
                    ],
                ],
            ])->id,
            'customer_code' => '90301',
            'customer_full_name' => '90301 - Công ty B',
            'customer_type' => 'Khách thường',
            'recipient_email' => null,
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => 'Không có email',
            'failed_at' => now(),
        ]);

        $previewService = Mockery::mock(BuildMailCampaignRecipientPreviewService::class);
        $previewService->shouldReceive('build')
            ->once()
            ->andReturn([
                'recipient' => [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'customerType' => $recipient->customer_type,
                ],
                'subject' => [
                    'renderedText' => 'Thư chiết khấu tháng 6',
                    'errors' => [],
                ],
                'greeting' => [
                    'renderedText' => 'Kính gửi Quý khách',
                    'errors' => [],
                ],
                'tables' => [],
                'errors' => [],
                'html' => '<html><body><h1>Preview mail</h1></body></html>',
            ]);
        app()->instance(BuildMailCampaignRecipientPreviewService::class, $previewService);

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'completed_with_failures',
        ]);
    }

    public function test_dispatch_job_snapshots_normal_customer_representative_signature_from_canvas(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);

        $this->bindRepresentativeSignatureToCanvas($campaign->templateCanvas, $user->id, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                'representativeRole' => 'Giám đốc kinh doanh',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5LXNpZw==',
                'representativeRole' => 'Giám đốc Key Account',
                'representativeName' => 'Trần Thị B',
            ],
        ]);

        $this->mockSuccessfulPreviewBuild($campaign, $recipient);

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        $recipient->refresh();

        $this->assertSame([
            'partType' => 'representative-signature',
            'customerType' => 'Khách thường',
            'title' => 'Đại diện công ty',
            'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
            'representativeRole' => 'Giám đốc kinh doanh',
            'representativeName' => 'Nguyễn Văn A',
        ], $recipient->sent_signature_snapshot);
    }

    public function test_dispatch_job_snapshots_key_account_representative_signature_from_canvas(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user, customerType: 'Key Account');

        $this->bindRepresentativeSignatureToCanvas($campaign->templateCanvas, $user->id, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                'representativeRole' => 'Giám đốc kinh doanh',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5LXNpZw==',
                'representativeRole' => 'Giám đốc Key Account',
                'representativeName' => 'Trần Thị B',
            ],
        ]);

        $this->mockSuccessfulPreviewBuild($campaign, $recipient);

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        $recipient->refresh();

        $this->assertSame([
            'partType' => 'representative-signature',
            'customerType' => 'Key Account',
            'title' => 'Đại diện công ty',
            'signatureImageDataUrl' => 'data:image/png;base64,a2V5LXNpZw==',
            'representativeRole' => 'Giám đốc Key Account',
            'representativeName' => 'Trần Thị B',
        ], $recipient->sent_signature_snapshot);
    }

    public function test_dispatch_job_marks_recipient_failed_when_signature_block_is_missing_for_customer_type(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);

        $this->bindRepresentativeSignatureToCanvas($campaign->templateCanvas, $user->id, [
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5LXNpZw==',
                'representativeRole' => 'Giám đốc Key Account',
                'representativeName' => 'Trần Thị B',
            ],
        ]);

        $this->mockSuccessfulPreviewBuild($campaign, $recipient);

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
        );

        Mail::assertNothingSent();

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'failed',
            'attempts_count' => 1,
        ]);

        $recipient->refresh();

        $this->assertNull($recipient->sent_signature_snapshot);
        $this->assertNotNull($recipient->latest_error_message);
        $this->assertStringContainsString('Khách thường', $recipient->latest_error_message);
        $this->assertStringContainsString('chữ ký', $recipient->latest_error_message);
    }

    /**
     * @return array{0: MailCampaign, 1: MailCampaignRecipient}
     */
    private function makeQueuedRecipientFixture(User $user, string $customerType = 'Khách thường'): array
    {
        $batch = ImportBatch::query()->create([
            'batch_code' => 'IMP-2026-06',
            'name' => 'Batch tháng 6/2026',
            'original_file_name' => 'thang-6.xlsx',
            'stored_path' => 'imports/tmp/thang-6.xlsx',
            'uploaded_by' => $user->id,
            'status' => 'validated_ready',
        ]);

        $record = ImportBatchAggregatedRecord::query()->create([
            'import_batch_id' => $batch->id,
            'customer_code' => '90300',
            'customer_type' => $customerType,
            'source_sheets' => $customerType === 'Key Account' ? ['Key Account'] : ['Tổng hợp'],
            'aggregated_payload' => [
                'customerCode' => '90300',
                'customerFullName' => '90300 - Công ty A',
                'customerType' => $customerType,
                'tongHop' => [
                    'email' => 'send@example.com',
                ],
            ],
        ]);

        $canvas = MailTemplateCanvas::query()->create([
            'name' => 'Mẫu gửi mail tháng 6',
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $campaign = MailCampaign::query()->create([
            'name' => 'Chiến dịch tháng 6',
            'import_batch_id' => $batch->id,
            'mail_template_canvas_id' => $canvas->id,
            'notes' => 'Gửi thật',
            'status' => 'dispatching',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $recipient = MailCampaignRecipient::query()->create([
            'mail_campaign_id' => $campaign->id,
            'import_batch_aggregated_record_id' => $record->id,
            'customer_code' => '90300',
            'customer_full_name' => '90300 - Công ty A',
            'customer_type' => $customerType,
            'recipient_email' => 'send@example.com',
            'delivery_status' => 'queued',
            'attempts_count' => 0,
        ]);

        return [$campaign, $recipient];
    }

    /**
     * @param  array<string, array<string, string>>  $blocks
     */
    private function bindRepresentativeSignatureToCanvas(MailTemplateCanvas $canvas, int $userId, array $blocks): void
    {
        $signaturePart = app(EnsureTemplatePartCatalogPersistedService::class)->ensure()['representative-signature'];

        $version = TemplatePartVersion::query()->create([
            'template_part_id' => $signaturePart->id,
            'version_no' => 1,
            'version_label' => 'Signature v1',
            'structure_json' => [
                'type' => 'representative-signature',
                'label' => 'Khối chữ ký đại diện',
                'kind' => 'composite',
                'blocks' => $blocks,
            ],
            'legacy_mail_template_id' => $canvas->legacy_mail_template_id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        MailTemplateCanvasPart::query()->updateOrCreate(
            [
                'mail_template_canvas_id' => $canvas->id,
                'template_part_id' => $signaturePart->id,
            ],
            [
                'template_part_version_id' => $version->id,
                'sort_order' => 99,
            ],
        );
    }

    private function mockSuccessfulPreviewBuild(MailCampaign $campaign, MailCampaignRecipient $recipient): void
    {
        $previewService = Mockery::mock(BuildMailCampaignRecipientPreviewService::class);
        $previewService->shouldReceive('build')
            ->once()
            ->withArgs(fn (MailCampaign $resolvedCampaign, int $recipientId): bool => $resolvedCampaign->id === $campaign->id && $recipientId === $recipient->id)
            ->andReturn([
                'recipient' => [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'customerType' => $recipient->customer_type,
                ],
                'subject' => [
                    'renderedText' => 'Thư chiết khấu tháng 6',
                    'errors' => [],
                ],
                'greeting' => [
                    'renderedText' => 'Kính gửi Quý khách',
                    'errors' => [],
                ],
                'tables' => [],
                'errors' => [],
                'html' => '<html><body><h1>Preview mail</h1></body></html>',
            ]);
        app()->instance(BuildMailCampaignRecipientPreviewService::class, $previewService);
    }
}
