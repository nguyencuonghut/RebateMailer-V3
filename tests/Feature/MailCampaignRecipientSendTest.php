<?php

namespace Tests\Feature;

use App\Jobs\DispatchMailCampaignRecipientJob;
use App\Mail\MailCampaignRecipientMail;
use App\Models\ImportBatch;
use App\Models\ImportBatchAggregatedRecord;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvasPart;
use App\Models\MailTemplateCanvas;
use App\Models\TemplatePartVersion;
use App\Models\User;
use App\Services\Mail\BuildMailCampaignRecipientEmailHtmlService;
use App\Services\Mail\BuildMailCampaignRecipientPreviewService;
use App\Services\Mail\BuildRepresentativeSignatureSnapshotService;
use App\Services\Mail\ClassifyMailDispatchExceptionService;
use App\Services\Mail\LogMailCampaignRecipientAttemptService;
use App\Services\Mail\UpdateMailCampaignDispatchStatusService;
use App\Services\Templates\EnsureTemplatePartCatalogPersistedService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
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

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $this->attachRepresentativeSignatureToCampaignCanvas($campaign, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFs',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5',
                'representativeRole' => 'Giám đốc kinh doanh KA',
                'representativeName' => 'Trần Thị B',
            ],
        ]);

        $previewService = Mockery::mock(BuildMailCampaignRecipientPreviewService::class);
        $capturedHtmlBody = null;
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

        Mail::assertNothingSent();

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
            app(ClassifyMailDispatchExceptionService::class),
        );

        Mail::assertSent(MailCampaignRecipientMail::class, function (MailCampaignRecipientMail $mail) use ($recipient): bool {
            $GLOBALS['__mail_campaign_snapshot_html'] = $mail->htmlBody;

            return $mail->hasTo((string) $recipient->recipient_email)
                && $mail->subjectLine === 'Thư chiết khấu tháng 6';
        });

        $capturedHtmlBody = $GLOBALS['__mail_campaign_snapshot_html'] ?? null;
        unset($GLOBALS['__mail_campaign_snapshot_html']);

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'sent',
            'attempts_count' => 1,
            'latest_error_message' => null,
            'sent_subject_snapshot' => 'Thư chiết khấu tháng 6',
            'snapshot_version' => 1,
        ]);

        $recipient->refresh();

        $this->assertSame($capturedHtmlBody, $recipient->sent_html_snapshot);
        $this->assertSame('Trưởng ban tài chính', $recipient->sent_signature_snapshot['representativeRole']);
        $this->assertSame('Nguyễn Văn A', $recipient->sent_signature_snapshot['representativeName']);
        $this->assertSame('Đại diện công ty', $recipient->sent_signature_snapshot['title']);

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

    public function test_dispatch_job_snapshots_key_account_signature_block_for_key_account_customer(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user, customerType: 'Key Account');
        $this->attachRepresentativeSignatureToCampaignCanvas($campaign, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFs',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
            'keyAccountCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,a2V5',
                'representativeRole' => 'Giám đốc kinh doanh KA',
                'representativeName' => 'Trần Thị B',
            ],
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
            app(ClassifyMailDispatchExceptionService::class),
        );

        $recipient->refresh();

        $this->assertSame('Giám đốc kinh doanh KA', $recipient->sent_signature_snapshot['representativeRole']);
        $this->assertSame('Trần Thị B', $recipient->sent_signature_snapshot['representativeName']);
    }

    public function test_dispatch_job_fails_before_sending_when_matching_signature_block_is_missing(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user, customerType: 'Key Account');
        $this->attachRepresentativeSignatureToCampaignCanvas($campaign, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFs',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
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
            app(ClassifyMailDispatchExceptionService::class),
        );

        Mail::assertNothingSent();

        $this->assertDatabaseHas('mail_campaign_recipients', [
            'id' => $recipient->id,
            'delivery_status' => 'failed',
            'attempts_count' => 1,
            'latest_error_message' => 'Thiếu cấu hình chữ ký đại diện cho loại khách hàng "Key Account".',
        ]);

        $recipient->refresh();
        $this->assertNull($recipient->sent_signature_snapshot);
    }

    public function test_dispatch_job_marks_campaign_completed_with_failures_when_other_recipient_failed(): void
    {
        Mail::fake();

        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);

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
            app(ClassifyMailDispatchExceptionService::class),
        );

        $this->assertDatabaseHas('mail_campaigns', [
            'id' => $campaign->id,
            'status' => 'completed_with_failures',
        ]);
    }

    public function test_dispatch_job_keeps_recipient_queued_when_smtp_error_is_transient(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $this->attachRepresentativeSignatureToCampaignCanvas($campaign, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFs',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
        ]);
        $this->mockSuccessfulPreview($campaign, $recipient);

        $exception = new TransportException('Expected response code "250" but got code "421", with message "421 4.7.0 Try again later, closing connection."');

        Mail::shouldReceive('to')
            ->once()
            ->with((string) $recipient->recipient_email)
            ->andReturn(new class($exception) {
                public function __construct(private readonly TransportException $exception)
                {
                }

                public function send(mixed $mail): void
                {
                    throw $this->exception;
                }
            });

        $job = new DispatchMailCampaignRecipientJob($recipient->id);

        try {
            $job->handle(
                app(BuildMailCampaignRecipientPreviewService::class),
                app(BuildMailCampaignRecipientEmailHtmlService::class),
                app(BuildRepresentativeSignatureSnapshotService::class),
                app(LogMailCampaignRecipientAttemptService::class),
                app(UpdateMailCampaignDispatchStatusService::class),
                app(ClassifyMailDispatchExceptionService::class),
            );

            $this->fail('Expected transient SMTP exception to be rethrown for queue retry.');
        } catch (TransportException $caught) {
            $this->assertSame($exception, $caught);
        }

        $recipient->refresh();
        $this->assertSame('queued', $recipient->delivery_status);
        $this->assertSame(1, $recipient->attempts_count);
        $this->assertSame($exception->getMessage(), $recipient->latest_error_message);
        $this->assertNull($recipient->failed_at);

        $attempt = $recipient->attemptLogs()->latest()->firstOrFail();
        $this->assertSame('dispatch_attempt_failed', $attempt->event_type);
        $this->assertSame('failed', $attempt->status);
        $this->assertTrue((bool) ($attempt->context['isTransient'] ?? false));
        $this->assertTrue((bool) ($attempt->context['willRetry'] ?? false));
    }

    public function test_dispatch_job_fails_permanently_when_smtp_reports_invalid_recipient(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $this->attachRepresentativeSignatureToCampaignCanvas($campaign, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFs',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
        ]);
        $this->mockSuccessfulPreview($campaign, $recipient);

        $exception = new TransportException('Expected response code "250" but got code "550", with message "550-5.1.1 The email account that you tried to reach does not exist."');

        Mail::shouldReceive('to')
            ->once()
            ->with((string) $recipient->recipient_email)
            ->andReturn(new class($exception) {
                public function __construct(private readonly TransportException $exception)
                {
                }

                public function send(mixed $mail): void
                {
                    throw $this->exception;
                }
            });

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
            app(ClassifyMailDispatchExceptionService::class),
        );

        $recipient->refresh();
        $this->assertSame('failed', $recipient->delivery_status);
        $this->assertSame(1, $recipient->attempts_count);
        $this->assertSame($exception->getMessage(), $recipient->latest_error_message);
        $this->assertNotNull($recipient->failed_at);

        $attempt = $recipient->attemptLogs()->latest()->firstOrFail();
        $this->assertSame('dispatch_attempt_failed', $attempt->event_type);
        $this->assertFalse((bool) ($attempt->context['isTransient'] ?? true));
        $this->assertFalse((bool) ($attempt->context['willRetry'] ?? true));
    }

    public function test_dispatch_job_fails_permanently_when_recipient_mailbox_is_full(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $this->attachRepresentativeSignatureToCampaignCanvas($campaign, [
            'normalCustomer' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFs',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
        ]);
        $this->mockSuccessfulPreview($campaign, $recipient);

        $exception = new TransportException('Expected response code "250" but got code "552", with message "552 5.2.2 The email account is over quota. Mailbox full."');

        Mail::shouldReceive('to')
            ->once()
            ->with((string) $recipient->recipient_email)
            ->andReturn(new class($exception) {
                public function __construct(private readonly TransportException $exception)
                {
                }

                public function send(mixed $mail): void
                {
                    throw $this->exception;
                }
            });

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->handle(
            app(BuildMailCampaignRecipientPreviewService::class),
            app(BuildMailCampaignRecipientEmailHtmlService::class),
            app(BuildRepresentativeSignatureSnapshotService::class),
            app(LogMailCampaignRecipientAttemptService::class),
            app(UpdateMailCampaignDispatchStatusService::class),
            app(ClassifyMailDispatchExceptionService::class),
        );

        $recipient->refresh();
        $this->assertSame('failed', $recipient->delivery_status);
        $this->assertSame(1, $recipient->attempts_count);
        $this->assertSame($exception->getMessage(), $recipient->latest_error_message);
        $this->assertNotNull($recipient->failed_at);

        $attempt = $recipient->attemptLogs()->latest()->firstOrFail();
        $this->assertSame('dispatch_attempt_failed', $attempt->event_type);
        $this->assertFalse((bool) ($attempt->context['isTransient'] ?? true));
        $this->assertFalse((bool) ($attempt->context['willRetry'] ?? true));
    }

    public function test_dispatch_job_final_failure_keeps_latest_smtp_message_for_user_visible_error(): void
    {
        $user = User::query()->where('email', 'user@rebatemailer.test')->firstOrFail();
        [$campaign, $recipient] = $this->makeQueuedRecipientFixture($user);
        $smtpMessage = 'Expected response code "250" but got code "421", with message "421 4.7.0 Try again later."';

        app(LogMailCampaignRecipientAttemptService::class)->log(
            $recipient,
            'dispatch_attempt_failed',
            'failed',
            $smtpMessage,
            [
                'exception' => TransportException::class,
                'isTransient' => true,
                'willRetry' => true,
            ],
        );

        $job = new DispatchMailCampaignRecipientJob($recipient->id);
        $job->failed(new MaxAttemptsExceededException('App\Jobs\DispatchMailCampaignRecipientJob has been attempted too many times.'));

        $recipient->refresh();
        $this->assertSame('failed', $recipient->delivery_status);
        $this->assertSame($smtpMessage, $recipient->latest_error_message);

        $attempt = $recipient->attemptLogs()->latest()->firstOrFail();
        $this->assertSame('dispatch_failed', $attempt->event_type);
        $this->assertSame($smtpMessage, $attempt->message);
        $this->assertSame('App\Jobs\DispatchMailCampaignRecipientJob has been attempted too many times.', $attempt->context['finalExceptionMessage'] ?? null);
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
            'source_sheets' => ['Tổng hợp'],
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

    private function mockSuccessfulPreview(MailCampaign $campaign, MailCampaignRecipient $recipient): void
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

    /**
     * @param  array<string, array<string, string|null>>  $blocks
     */
    private function attachRepresentativeSignatureToCampaignCanvas(MailCampaign $campaign, array $blocks): void
    {
        $parts = app(EnsureTemplatePartCatalogPersistedService::class)->ensure();
        $signaturePart = $parts['representative-signature'];
        $canvas = $campaign->templateCanvas()->firstOrFail();
        $nextVersionNo = (int) TemplatePartVersion::query()
            ->where('template_part_id', $signaturePart->id)
            ->max('version_no') + 1;

        $version = TemplatePartVersion::query()->create([
            'template_part_id' => $signaturePart->id,
            'version_no' => $nextVersionNo,
            'version_label' => 'Signature for campaign test',
            'structure_json' => [
                'type' => 'representative-signature',
                'label' => 'Chữ ký đại diện',
                'description' => 'Chữ ký người đại diện theo 2 block Khách thường và Key Account.',
                'kind' => 'composite',
                'blocks' => $blocks,
            ],
            'legacy_mail_template_id' => null,
            'created_by' => $campaign->created_by,
            'updated_by' => $campaign->updated_by,
        ]);

        MailTemplateCanvasPart::query()->create([
            'mail_template_canvas_id' => $canvas->id,
            'template_part_id' => $signaturePart->id,
            'template_part_version_id' => $version->id,
            'sort_order' => 2,
        ]);
    }
}
