<?php

namespace App\Services\Dashboard;

use App\Models\ImportBatch;
use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplateCanvas;
use App\Support\Authorization\PermissionName;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class DashboardPageService
{
    /**
     * @return array<string, mixed>
     */
    public function getPageData(?User $user = null): array
    {
        $activeImportStatuses = ['uploaded', 'analyzed', 'parsed'];
        $activeCampaignStatuses = ['scheduled', 'dispatching'];

        $importBatchCount = ImportBatch::query()->count();
        $readyImportBatchCount = ImportBatch::query()
            ->whereIn('status', ['aggregated', 'validated_ready', 'validated_with_warnings'])
            ->count();
        $processingImportBatchCount = ImportBatch::query()
            ->whereIn('status', $activeImportStatuses)
            ->count();
        $mailTemplateCount = MailTemplateCanvas::query()->count();
        $activeTemplateCount = MailTemplateCanvas::query()->where('is_active', true)->count();
        $mailCampaignCount = MailCampaign::query()->count();
        $dispatchingCampaignCount = MailCampaign::query()->where('status', 'dispatching')->count();
        $activeCampaignCount = MailCampaign::query()
            ->whereIn('status', $activeCampaignStatuses)
            ->count();
        $queuedRecipientCount = MailCampaignRecipient::query()->where('delivery_status', 'queued')->count();
        $sentRecipientCount = MailCampaignRecipient::query()->where('delivery_status', 'sent')->count();
        $failedRecipientCount = MailCampaignRecipient::query()->where('delivery_status', 'failed')->count();

        $latestImportBatch = ImportBatch::query()
            ->withCount('aggregatedRecords')
            ->latest('id')
            ->first();
        $activeTemplate = MailTemplateCanvas::query()
            ->withCount('partBindings')
            ->where('is_active', true)
            ->latest('updated_at')
            ->latest('id')
            ->first();
        $latestCampaign = MailCampaign::query()
            ->with(['importBatch', 'templateCanvas'])
            ->withCount('recipients')
            ->latest('id')
            ->first();
        $canViewImports = $user?->can(PermissionName::ImportsView->value) ?? false;
        $canViewTemplates = $user?->can(PermissionName::TemplatesView->value) ?? false;
        $canViewMail = $user?->can(PermissionName::MailView->value) ?? false;

        $shouldAutoRefresh = $processingImportBatchCount > 0 || $activeCampaignCount > 0;

        return [
            'title' => 'Bảng điều khiển vận hành',
            'subtitle' => 'Theo dõi toàn bộ tình trạng import dữ liệu, mẫu email và chiến dịch gửi mail trên một màn hình.',
            'autoRefresh' => [
                'enabled' => $shouldAutoRefresh,
                'intervalSeconds' => 5,
                'reason' => $shouldAutoRefresh
                    ? $this->buildAutoRefreshReason($processingImportBatchCount, $activeCampaignCount)
                    : 'Dashboard đang ở trạng thái ổn định, không cần tự động làm mới.',
                'lastUpdatedAt' => now()->toIso8601String(),
            ],
            'overviewCards' => [
                [
                    'label' => 'Batch import đã tạo',
                    'value' => $importBatchCount,
                    'caption' => sprintf('%d batch đã sẵn sàng aggregate/validate.', $readyImportBatchCount),
                    'icon' => 'pi pi-upload',
                    'severity' => 'info',
                ],
                [
                    'label' => 'Template canvas',
                    'value' => $mailTemplateCount,
                    'caption' => sprintf('%d template đang hoạt động.', $activeTemplateCount),
                    'icon' => 'pi pi-pencil',
                    'severity' => $activeTemplateCount > 0 ? 'success' : 'warn',
                ],
                [
                    'label' => 'Chiến dịch gửi mail',
                    'value' => $mailCampaignCount,
                    'caption' => sprintf('%d chiến dịch đang dispatch.', $dispatchingCampaignCount),
                    'icon' => 'pi pi-send',
                    'severity' => $dispatchingCampaignCount > 0 ? 'warn' : 'secondary',
                ],
                [
                    'label' => 'Recipient đã gửi thành công',
                    'value' => $sentRecipientCount,
                    'caption' => sprintf('%d queued / %d failed trên toàn hệ thống.', $queuedRecipientCount, $failedRecipientCount),
                    'icon' => 'pi pi-check-circle',
                    'severity' => $failedRecipientCount > 0 ? 'warn' : 'success',
                ],
            ],
            'operationalPanels' => [
                [
                    'title' => 'Batch import gần nhất',
                    'status' => $latestImportBatch ? $this->presentImportStatus((string) $latestImportBatch->status) : 'Chưa có batch',
                    'lines' => $latestImportBatch
                        ? [
                            sprintf('Mã batch: %s', $latestImportBatch->batch_code),
                            sprintf('Tên batch: %s', $latestImportBatch->name ?: 'Chưa đặt tên'),
                            sprintf('Số khách aggregate: %d', $latestImportBatch->aggregated_records_count),
                        ]
                        : ['Chưa có dữ liệu import nào được ghi nhận.'],
                    'action' => $latestImportBatch && $canViewImports
                        ? [
                            'label' => 'Mở batch này',
                            'href' => route('imports.index', ['batch' => $latestImportBatch->id]),
                        ]
                        : null,
                ],
                [
                    'title' => 'Template đang hoạt động',
                    'status' => $activeTemplate ? 'Sẵn sàng gửi' : 'Chưa kích hoạt',
                    'lines' => $activeTemplate
                        ? [
                            sprintf('Tên template: %s', $activeTemplate->name),
                            sprintf('Số part đang ghép: %d', $activeTemplate->part_bindings_count),
                            'Template này sẽ được ưu tiên khi người dùng tạo chiến dịch mới.',
                        ]
                        : ['Chưa có template canvas nào được kích hoạt.'],
                    'action' => $activeTemplate && $canViewTemplates
                        ? [
                            'label' => 'Mở template mail',
                            'href' => route('templates.index'),
                        ]
                        : null,
                ],
                [
                    'title' => 'Chiến dịch gần nhất',
                    'status' => $latestCampaign ? $this->presentCampaignStatus((string) $latestCampaign->status) : 'Chưa có chiến dịch',
                    'lines' => $latestCampaign
                        ? [
                            sprintf('Tên chiến dịch: %s', $latestCampaign->name),
                            sprintf('Batch: %s', $latestCampaign->importBatch?->batch_code ?? 'Không có batch'),
                            sprintf('Template: %s', $latestCampaign->templateCanvas?->name ?? 'Không có template'),
                        ]
                        : ['Chưa có chiến dịch gửi mail nào được tạo.'],
                    'action' => $latestCampaign && $canViewMail
                        ? [
                            'label' => 'Mở chiến dịch này',
                            'href' => route('mail.index', ['campaign' => $latestCampaign->id]),
                        ]
                        : null,
                ],
            ],
            'deliveryHealth' => [
                'queuedRecipients' => $queuedRecipientCount,
                'sentRecipients' => $sentRecipientCount,
                'failedRecipients' => $failedRecipientCount,
                'failureRatePercent' => ($sentRecipientCount + $failedRecipientCount) === 0
                    ? 0
                    : (int) round(($failedRecipientCount / max(1, ($sentRecipientCount + $failedRecipientCount))) * 100),
            ],
            'quickActions' => $this->buildQuickActions($user),
            'recentImportBatches' => ImportBatch::query()
                ->withCount('aggregatedRecords')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (ImportBatch $batch): array => [
                    'id' => $batch->id,
                    'batchCode' => $batch->batch_code,
                    'batchName' => $batch->name,
                    'status' => $batch->status,
                    'statusLabel' => $this->presentImportStatus((string) $batch->status),
                    'aggregatedRecordCount' => $batch->aggregated_records_count,
                    'completedAt' => optional($batch->completed_at)->toIso8601String(),
                    'href' => $canViewImports ? route('imports.index', ['batch' => $batch->id]) : null,
                ])
                ->values()
                ->all(),
            'recentCampaigns' => MailCampaign::query()
                ->with(['importBatch', 'templateCanvas'])
                ->withCount('recipients')
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (MailCampaign $campaign): array => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'statusLabel' => $this->presentCampaignStatus((string) $campaign->status),
                    'batchCode' => $campaign->importBatch?->batch_code,
                    'templateName' => $campaign->templateCanvas?->name,
                    'recipientCount' => $campaign->recipients_count,
                    'scheduledForAt' => optional($campaign->scheduled_for_at)->toIso8601String(),
                    'createdAt' => optional($campaign->created_at)->toIso8601String(),
                    'href' => $canViewMail ? route('mail.index', ['campaign' => $campaign->id]) : null,
                ])
                ->values()
                ->all(),
        ];
    }

    private function buildAutoRefreshReason(int $processingImportBatchCount, int $activeCampaignCount): string
    {
        $reasons = [];

        if ($processingImportBatchCount > 0) {
            $reasons[] = sprintf('%d batch import đang xử lý', $processingImportBatchCount);
        }

        if ($activeCampaignCount > 0) {
            $reasons[] = sprintf('%d chiến dịch đang lên lịch hoặc gửi mail', $activeCampaignCount);
        }

        return 'Dashboard đang tự động làm mới vì còn '.$this->joinReasons($reasons).'.';
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function joinReasons(array $reasons): string
    {
        if (count($reasons) <= 1) {
            return $reasons[0] ?? 'hoạt động đang diễn ra';
        }

        $lastReason = array_pop($reasons);

        return implode(', ', $reasons).' và '.$lastReason;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildQuickActions(?User $user): array
    {
        $actions = [];

        if ($user?->can(PermissionName::ImportsView->value)) {
            $actions[] = [
                'label' => 'Mở import dữ liệu',
                'description' => 'Upload file Excel, parse workbook và aggregate theo Mã số.',
                'routeName' => 'imports.index',
            ];
        }

        if ($user?->can(PermissionName::TemplatesView->value)) {
            $actions[] = [
                'label' => 'Mở thiết kế mẫu email',
                'description' => 'Quản lý canvas, part versions và preview theo batch import.',
                'routeName' => 'templates.index',
            ];
        }

        if ($user?->can(PermissionName::MailView->value)) {
            $actions[] = [
                'label' => 'Mở điều phối gửi mail',
                'description' => 'Tạo chiến dịch, xem preview email và theo dõi tiến độ gửi.',
                'routeName' => 'mail.index',
            ];
        }

        if ($user?->can(PermissionName::UsersView->value)) {
            $actions[] = [
                'label' => 'Mở quản lý người dùng',
                'description' => 'Rà soát role, permission và tài khoản được cấp quyền.',
                'routeName' => 'users.index',
            ];
        }

        return $actions;
    }

    private function presentImportStatus(string $status): string
    {
        return match ($status) {
            'uploaded' => 'Đã upload',
            'analyzed' => 'Đã đọc workbook',
            'parsed' => 'Đã parse',
            'aggregated' => 'Đã aggregate',
            'validated_ready' => 'Hợp lệ, sẵn sàng dùng',
            'validated_with_warnings' => 'Có cảnh báo',
            'failed' => 'Lỗi xử lý',
            default => $status,
        };
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
}
