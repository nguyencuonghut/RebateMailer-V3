<?php

namespace App\Services\Mail;

use App\Models\ImportBatch;
use App\Models\MailCampaign;
use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use RuntimeException;

class MailCampaignPageService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPreviewService $buildMailCampaignRecipientPreviewService,
        private readonly BuildMailCampaignRecipientPdfPayloadService $buildMailCampaignRecipientPdfPayloadService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexPageData(bool $canManageCampaigns, ?int $selectedCampaignId = null, ?int $selectedRecipientId = null): array
    {
        $selectedCampaign = $this->resolveSelectedCampaign($selectedCampaignId);
        $selectedRecipientPreview = $selectedCampaign
            ? $this->buildMailCampaignRecipientPreviewService->build($selectedCampaign, $selectedRecipientId)
            : null;

        return [
            'title' => 'Điều phối gửi mail',
            'description' => 'Tạo chiến dịch gửi mail từ batch dữ liệu đã aggregate và template email đã thiết kế, sau đó theo dõi danh sách người nhận ngay trên một màn hình.',
            'canManageCampaigns' => $canManageCampaigns,
            'readOnlyNotice' => 'Tài khoản hiện tại chỉ được xem chiến dịch gửi mail. Các thao tác tạo chiến dịch và điều phối gửi chỉ mở cho người dùng có quyền gửi mail.',
            'batchOptions' => $this->buildBatchOptions(),
            'templateOptions' => $this->buildTemplateOptions(),
            'campaignOptions' => $this->buildCampaignOptions(),
            'selectedCampaignId' => $selectedCampaign?->id,
            'selectedCampaign' => $selectedCampaign ? $this->presentCampaign($selectedCampaign) : null,
            'selectedRecipientId' => $selectedRecipientPreview['recipient']['id'] ?? null,
            'selectedRecipientPreview' => $selectedRecipientPreview,
            'recipientList' => $selectedCampaign ? $this->buildRecipientList($selectedCampaign) : [],
        ];
    }

    private function resolveSelectedCampaign(?int $selectedCampaignId): ?MailCampaign
    {
        $query = MailCampaign::query()
            ->with([
                'importBatch',
                'templateCanvas',
                'creator',
                'recipients',
                'exports' => fn ($query) => $query->latest('requested_at')->latest('id'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($selectedCampaignId !== null) {
            $selectedQuery = clone $query;

            return $selectedQuery->find($selectedCampaignId) ?? $query->first();
        }

        return $query->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBatchOptions(): array
    {
        return ImportBatch::query()
            ->withCount('aggregatedRecords')
            ->whereIn('status', ['aggregated', 'validated_ready', 'validated_with_warnings'])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (ImportBatch $batch): bool => $batch->aggregated_records_count > 0)
            ->map(function (ImportBatch $batch): array {
                $errorCount = (int) ($batch->workbook_summary['aggregatePreview']['summary']['errorCount'] ?? 0);

                return [
                    'batchId' => $batch->id,
                    'batchCode' => $batch->batch_code,
                    'batchName' => $batch->name,
                    'errorCount' => $errorCount,
                    'hasErrors' => $errorCount > 0,
                    'label' => trim(sprintf('%s - %s (%d khách)', $batch->batch_code, $batch->name ?: 'Chưa đặt tên', $batch->aggregated_records_count)),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplateOptions(): array
    {
        return MailTemplateCanvas::query()
            ->withCount('partBindings')
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MailTemplateCanvas $canvas): array => [
                'canvasId' => $canvas->id,
                'name' => $canvas->name,
                'isActive' => $canvas->is_active,
                'partCount' => $canvas->part_bindings_count,
                'label' => trim(sprintf('%s%s (%d phần)', $canvas->name, $canvas->is_active ? ' - Đang hoạt động' : '', $canvas->part_bindings_count)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCampaignOptions(): array
    {
        return MailCampaign::query()
            ->with(['importBatch', 'creator'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (MailCampaign $campaign): array => [
                'campaignId' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'createdBy' => $campaign->creator?->name ?? 'Không xác định',
                'createdAt' => optional($campaign->created_at)->toIso8601String(),
                'label' => trim(sprintf(
                    '%s - Người tạo: %s - Tạo lúc: %s',
                    $campaign->name,
                    $campaign->creator?->name ?? 'Không xác định',
                    optional($campaign->created_at)->format('d/m/Y H:i') ?? 'Không xác định',
                )),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentCampaign(MailCampaign $campaign): array
    {
        $recipientSummary = [
            'total' => $campaign->recipients->count(),
            'pending' => $campaign->recipients->where('delivery_status', 'pending')->count(),
            'queued' => $campaign->recipients->where('delivery_status', 'queued')->count(),
            'sent' => $campaign->recipients->where('delivery_status', 'sent')->count(),
            'failed' => $campaign->recipients->where('delivery_status', 'failed')->count(),
        ];

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'notes' => $campaign->notes,
            'status' => $campaign->status,
            'statusLabel' => $this->presentCampaignStatus($campaign->status),
            'dispatchTrigger' => $campaign->dispatch_trigger,
            'scheduledAt' => optional($campaign->scheduled_at)->toIso8601String(),
            'scheduledForAt' => optional($campaign->scheduled_for_at)->toIso8601String(),
            'createdBy' => $campaign->creator?->name ?? 'Không xác định',
            'createdAt' => optional($campaign->created_at)->toIso8601String(),
            'batch' => [
                'id' => $campaign->importBatch?->id,
                'batchCode' => $campaign->importBatch?->batch_code,
                'batchName' => $campaign->importBatch?->name,
            ],
            'template' => [
                'id' => $campaign->templateCanvas?->id,
                'name' => $campaign->templateCanvas?->name,
                'isActive' => $campaign->templateCanvas?->is_active ?? false,
            ],
            'recipientSummary' => $recipientSummary,
            'progress' => $this->buildProgressPayload($campaign, $recipientSummary),
            'canRequestPdfExport' => $this->canRequestPdfExport($campaign),
            'pdfExportDisabledReason' => $this->resolvePdfExportDisabledReason($campaign),
            'latestPdfExport' => $this->presentLatestPdfExport($campaign),
        ];
    }

    private function canRequestPdfExport(MailCampaign $campaign): bool
    {
        return $this->resolvePdfExportDisabledReason($campaign) === null;
    }

    private function resolvePdfExportDisabledReason(MailCampaign $campaign): ?string
    {
        if ($campaign->recipients->count() === 0) {
            return 'Chiến dịch chưa có người nhận để tạo export PDF.';
        }

        $hasInProgressExport = $campaign->exports
            ->first(fn (MailCampaignExport $export): bool => $export->export_type === 'pdf' && in_array($export->status, ['queued', 'processing'], true));

        if ($hasInProgressExport) {
            return 'Chiến dịch đang có yêu cầu export PDF chưa hoàn tất.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentLatestPdfExport(MailCampaign $campaign): ?array
    {
        $export = $campaign->exports
            ->first(fn (MailCampaignExport $item): bool => $item->export_type === 'pdf');

        if (! $export) {
            return null;
        }

        return [
            'id' => $export->id,
            'status' => $export->status,
            'statusLabel' => $this->presentPdfExportStatus($export->status),
            'totalRecipients' => $export->total_recipients,
            'exportedRecipients' => $export->exported_recipients,
            'requestedAt' => optional($export->requested_at)->toIso8601String(),
            'startedAt' => optional($export->started_at)->toIso8601String(),
            'completedAt' => optional($export->completed_at)->toIso8601String(),
            'failedAt' => optional($export->failed_at)->toIso8601String(),
            'errorMessage' => $export->error_message,
            'fileName' => $export->file_name,
        ];
    }

    /**
     * @param  array{total: int, pending: int, queued: int, sent: int, failed: int}  $recipientSummary
     * @return array<string, mixed>
     */
    private function buildProgressPayload(MailCampaign $campaign, array $recipientSummary): array
    {
        $total = max(1, $recipientSummary['total']);
        $processed = $recipientSummary['sent'] + $recipientSummary['failed'];
        $inFlight = $recipientSummary['queued'];

        return [
            'batchId' => $campaign->import_batch_id,
            'batchCode' => $campaign->importBatch?->batch_code,
            'totalRecipients' => $recipientSummary['total'],
            'processedRecipients' => $processed,
            'queuedRecipients' => $inFlight,
            'sentRecipients' => $recipientSummary['sent'],
            'failedRecipients' => $recipientSummary['failed'],
            'pendingRecipients' => $recipientSummary['pending'],
            'completionPercent' => $recipientSummary['total'] === 0
                ? 0
                : (int) round(($processed / $total) * 100),
            'sentPercent' => $recipientSummary['total'] === 0
                ? 0
                : (int) round(($recipientSummary['sent'] / $total) * 100),
            'failedPercent' => $recipientSummary['total'] === 0
                ? 0
                : (int) round(($recipientSummary['failed'] / $total) * 100),
            'queuedPercent' => $recipientSummary['total'] === 0
                ? 0
                : (int) round(($inFlight / $total) * 100),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRecipientList(MailCampaign $campaign): array
    {
        $recipients = MailCampaignRecipient::query()
            ->where('mail_campaign_id', $campaign->id)
            ->with(['aggregatedRecord', 'attemptLogs' => fn ($q) => $q->latest()->limit(20)])
            ->orderBy('customer_code')
            ->get();

        $recipientGroupCounts = $recipients
            ->groupBy(fn (MailCampaignRecipient $recipient): string => $this->resolveRecipientGroupKey($recipient))
            ->map(fn ($group): int => $group->count());

        return $recipients
            ->map(function (MailCampaignRecipient $recipient) use ($campaign, $recipientGroupCounts): array {
                $previewIssues = $this->collectPreviewIssues($campaign, $recipient);

                return [
                    'id' => $recipient->id,
                    'customerCode' => $recipient->customer_code,
                    'customerFullName' => $recipient->customer_full_name,
                    'recipientEmail' => $recipient->recipient_email,
                    'aggregatedEmails' => $this->extractAggregatedEmails($recipient),
                    'relatedRecipientCount' => $recipientGroupCounts->get($this->resolveRecipientGroupKey($recipient), 1),
                    'recipientGroupLabel' => $this->presentRecipientGroupLabel(
                        $recipientGroupCounts->get($this->resolveRecipientGroupKey($recipient), 1),
                    ),
                    'sourceSheets' => $recipient->aggregatedRecord?->source_sheets ?? [],
                    'sourceSheetsLabel' => $this->presentSourceSheets($recipient->aggregatedRecord?->source_sheets),
                    'deliveryStatus' => $recipient->delivery_status,
                    'deliveryStatusLabel' => $this->presentRecipientStatus($recipient->delivery_status),
                    'latestErrorMessage' => $recipient->latest_error_message,
                    'latestFriendlyMessage' => $this->resolveLatestFriendlyMessage($recipient),
                    'attemptsCount' => $recipient->attempts_count,
                    'previewIssues' => $previewIssues,
                    'previewIssueCount' => count($previewIssues),
                    'canExportPdf' => $this->hasSentSnapshot($recipient)
                        || ($previewIssues === [] && $this->canExportRecipientPdf($campaign, $recipient)),
                    'canRetry' => $recipient->delivery_status === 'failed',
                    'canResend' => $recipient->delivery_status === 'sent' && $campaign->status !== 'cancelled',
                    'attemptLogs' => $recipient->attemptLogs->map(fn ($attempt): array => [
                        'id' => $attempt->id,
                        'eventType' => $attempt->event_type,
                        'eventLabel' => $this->presentRecipientAttemptEvent($attempt->event_type),
                        'status' => $attempt->status,
                        'statusLabel' => $this->presentRecipientAttemptStatus($attempt->status),
                        'message' => $attempt->message,
                        'friendlyMessage' => $this->presentAttemptFriendlyMessage($attempt->event_type, $attempt->message),
                        'createdAt' => optional($attempt->created_at)->toIso8601String(),
                        'context' => $attempt->context ?? [],
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{section: string, message: string}>
     */
    private function collectPreviewIssues(MailCampaign $campaign, MailCampaignRecipient $recipient): array
    {
        $preview = $this->buildMailCampaignRecipientPreviewService->build($campaign, $recipient->id);

        if (! is_array($preview)) {
            return [[
                'section' => 'Chung',
                'message' => sprintf('Không dựng được preview cho khách hàng %s.', $recipient->customer_code),
            ]];
        }

        $issues = [];

        foreach ($preview['errors'] ?? [] as $message) {
            $this->appendPreviewIssue($issues, 'Chung', $message);
        }

        foreach ($preview['subject']['errors'] ?? [] as $message) {
            $this->appendPreviewIssue($issues, 'Tiêu đề', $message);
        }

        foreach ($preview['greeting']['errors'] ?? [] as $message) {
            $this->appendPreviewIssue($issues, 'Lời chào', $message);
        }

        foreach ($preview['tables'] ?? [] as $table) {
            if (! is_array($table)) {
                continue;
            }

            $section = trim((string) ($table['label'] ?? 'Bảng dữ liệu'));

            foreach ($table['errors'] ?? [] as $message) {
                $this->appendPreviewIssue($issues, $section !== '' ? $section : 'Bảng dữ liệu', $message);
            }
        }

        return $issues;
    }

    /**
     * @param  list<array{section: string, message: string}>  $issues
     */
    private function appendPreviewIssue(array &$issues, string $section, mixed $message): void
    {
        $message = trim((string) $message);

        if ($message === '') {
            return;
        }

        $issues[] = [
            'section' => $section,
            'message' => $message,
        ];
    }

    private function resolveRecipientGroupKey(MailCampaignRecipient $recipient): string
    {
        if ($recipient->import_batch_aggregated_record_id !== null) {
            return 'aggregated-record:'.$recipient->import_batch_aggregated_record_id;
        }

        return 'recipient:'.$recipient->id;
    }

    /**
     * @return array<int, string>
     */
    private function extractAggregatedEmails(MailCampaignRecipient $recipient): array
    {
        $emails = $recipient->aggregatedRecord?->aggregated_payload['emails'] ?? null;

        if (! is_array($emails)) {
            return $recipient->recipient_email ? [(string) $recipient->recipient_email] : [];
        }

        return array_values(array_filter(
            array_map(static fn ($value): string => trim((string) $value), $emails),
            static fn (string $value): bool => $value !== '',
        ));
    }

    private function presentRecipientGroupLabel(int $count): string
    {
        if ($count <= 1) {
            return '1 địa chỉ nhận cho khách hàng này';
        }

        return sprintf('%d địa chỉ nhận cho cùng khách hàng', $count);
    }

    private function canExportRecipientPdf(MailCampaign $campaign, MailCampaignRecipient $recipient): bool
    {
        try {
            $this->buildMailCampaignRecipientPdfPayloadService->build($campaign, $recipient);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    private function hasSentSnapshot(MailCampaignRecipient $recipient): bool
    {
        return filled($recipient->sent_subject_snapshot)
            && filled($recipient->sent_html_snapshot)
            && is_array($recipient->sent_signature_snapshot);
    }

    private function resolveLatestFriendlyMessage(MailCampaignRecipient $recipient): ?string
    {
        if ($recipient->latest_error_message === null) {
            return null;
        }

        $latestFailedAttempt = $recipient->attemptLogs
            ->first(fn ($attempt): bool => $attempt->status === 'failed');

        return $this->presentAttemptFriendlyMessage(
            $latestFailedAttempt?->event_type ?? 'dispatch_attempt_failed',
            $recipient->latest_error_message,
        );
    }

    /**
     * @param  mixed  $sourceSheets
     */
    private function presentSourceSheets(mixed $sourceSheets): string
    {
        if (! is_array($sourceSheets) || $sourceSheets === []) {
            return 'Không xác định';
        }

        return implode(' | ', array_map(static fn ($value): string => (string) $value, $sourceSheets));
    }

    private function presentCampaignStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Nháp',
            'scheduled' => 'Đã lên lịch',
            'dispatching' => 'Đang gửi',
            'completed' => 'Hoàn thành',
            'completed_with_failures' => 'Hoàn thành có lỗi',
            'paused' => 'Tạm dừng',
            'cancelled' => 'Đã hủy',
            default => $status,
        };
    }

    private function presentRecipientStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Chưa gửi',
            'queued' => 'Đã vào hàng đợi',
            'sending' => 'Đang gửi',
            'sent' => 'Đã gửi',
            'failed' => 'Lỗi gửi',
            default => $status,
        };
    }

    private function presentRecipientAttemptEvent(string $eventType): string
    {
        return match ($eventType) {
            'queued' => 'Đưa vào hàng đợi',
            'queue_blocked' => 'Chặn ở bước vào hàng đợi',
            'render_failed' => 'Render email thất bại',
            'sent' => 'Gửi mail thành công',
            'retry_queued' => 'Retry và đưa lại vào hàng đợi',
            'retry_blocked' => 'Retry bị chặn',
            'dispatch_attempt_failed' => 'Lần gửi mail này báo lỗi',
            'dispatch_failed' => 'Worker gửi mail báo lỗi cuối cùng',
            default => $eventType,
        };
    }

    private function presentRecipientAttemptStatus(string $status): string
    {
        return match ($status) {
            'queued' => 'Đã vào hàng đợi',
            'failed' => 'Thất bại',
            'success' => 'Thành công',
            default => $status,
        };
    }

    private function presentPdfExportStatus(string $status): string
    {
        return match ($status) {
            'queued' => 'Đang chờ tạo file',
            'processing' => 'Đang tạo file',
            'completed' => 'Đã tạo xong',
            'failed' => 'Tạo file thất bại',
            default => $status,
        };
    }

    private function presentAttemptFriendlyMessage(string $eventType, ?string $message): string
    {
        // Events that are already in Vietnamese or informational — return as-is
        if (in_array($eventType, ['queued', 'retry_queued', 'sent'], true)) {
            return $message ?? '';
        }

        // Our own render/block errors are already written in Vietnamese
        if (in_array($eventType, ['render_failed', 'queue_blocked', 'retry_blocked'], true)) {
            return $message ?? 'Không có thông tin chi tiết.';
        }

        if ($message === null || $message === '') {
            return 'Gửi email thất bại. Không có thông tin chi tiết về lỗi.';
        }

        $lower = strtolower($message);

        // Email address does not exist
        if (
            str_contains($lower, 'does not exist')
            || str_contains($lower, 'no such user')
            || str_contains($lower, 'user unknown')
            || str_contains($lower, 'invalid address')
            || str_contains($lower, 'bad destination')
            || str_contains($lower, 'mailbox not found')
            || str_contains($lower, 'invalid recipient')
            || str_contains($lower, 'address rejected')
            || str_contains($lower, 'recipient address')
            || (bool) preg_match('/\b55[013]\b/', $message)
        ) {
            return 'Địa chỉ email người nhận không tồn tại hoặc không hợp lệ. Kiểm tra lại email của khách hàng.';
        }

        // Spam / policy rejected
        if (
            str_contains($lower, 'spam')
            || str_contains($lower, 'blacklist')
            || str_contains($lower, 'blocked by policy')
            || str_contains($lower, 'rejected')
            || (bool) preg_match('/\b55[24]\b/', $message)
        ) {
            return 'Email bị từ chối bởi máy chủ người nhận (có thể bị đánh dấu spam hoặc bị chặn theo chính sách bảo mật).';
        }

        // Authentication failure
        if (
            str_contains($lower, 'authentication failed')
            || str_contains($lower, 'authenticate')
            || str_contains($lower, 'username and password')
            || str_contains($lower, 'invalid credentials')
            || (bool) preg_match('/\b53[045]\b/', $message)
        ) {
            return 'Xác thực với máy chủ mail thất bại. Kiểm tra lại tên đăng nhập và mật khẩu SMTP trong cấu hình hệ thống.';
        }

        // Connection timeout
        if (
            str_contains($lower, 'timed out')
            || str_contains($lower, 'timeout')
            || str_contains($lower, 'etimedout')
        ) {
            return 'Kết nối đến máy chủ mail bị timeout. Máy chủ có thể đang quá tải hoặc không phản hồi.';
        }

        // Connection refused / unreachable
        if (
            str_contains($lower, 'connection refused')
            || str_contains($lower, 'econnrefused')
            || str_contains($lower, 'could not connect')
            || str_contains($lower, 'unable to connect')
            || str_contains($lower, 'failed to connect')
        ) {
            return 'Không thể kết nối đến máy chủ mail. Kiểm tra lại địa chỉ host và port SMTP trong cấu hình.';
        }

        // DNS resolution failure
        if (
            str_contains($lower, 'getaddrinfo')
            || str_contains($lower, 'name or service not known')
            || str_contains($lower, 'nxdomain')
            || str_contains($lower, 'no such host')
        ) {
            return 'Không tìm thấy máy chủ mail (lỗi DNS). Kiểm tra lại giá trị MAIL_HOST trong cấu hình.';
        }

        // SSL / TLS errors
        if (
            str_contains($lower, 'ssl')
            || str_contains($lower, 'tls')
            || str_contains($lower, 'crypto')
            || str_contains($lower, 'certificate')
            || str_contains($lower, 'handshake')
        ) {
            return 'Lỗi kết nối bảo mật SSL/TLS với máy chủ mail. Kiểm tra lại cấu hình MAIL_SCHEME và port.';
        }

        // Mailbox full
        if (
            str_contains($lower, 'mailbox full')
            || str_contains($lower, 'quota exceeded')
            || str_contains($lower, 'over quota')
            || str_contains($lower, 'insufficient storage')
        ) {
            return 'Hộp thư của người nhận đã đầy. Không thể gửi email vào thời điểm này.';
        }

        // Queue max attempts exhausted (internal, not a mail server error)
        if (str_contains($lower, 'has been attempted too many times') || str_contains($lower, 'maxattemptsexceededexception')) {
            return 'Hệ thống đã thử gửi mail nhiều lần nhưng đều thất bại. Vui lòng kiểm tra cấu hình mail và thử lại thủ công.';
        }

        // Rate limiting / temporary deferral
        if (
            str_contains($lower, 'too many')
            || str_contains($lower, 'rate limit')
            || str_contains($lower, 'throttle')
            || (bool) preg_match('/\b42[01]\b/', $message)
            || (bool) preg_match('/\b450\b/', $message)
        ) {
            return 'Gửi mail bị giới hạn tốc độ bởi máy chủ. Hệ thống sẽ tự động thử lại sau.';
        }

        // Generic SMTP protocol error
        if (str_contains($lower, 'expected response code') || str_contains($lower, 'smtp')) {
            return 'Máy chủ mail từ chối yêu cầu gửi. Xem chi tiết kỹ thuật để biết thêm thông tin.';
        }

        return 'Gửi email thất bại. Xem chi tiết kỹ thuật để biết thêm thông tin.';
    }
}
