<?php

namespace App\Services\Templates;

use App\Models\MailTemplateCanvas;
use App\Models\MailTemplate;

class TemplatePageService
{
    public function __construct(
        private readonly TemplatePartCatalogService $templatePartCatalogService,
        private readonly TemplateVariableCatalogService $templateVariableCatalogService,
        private readonly BuildTemplateSubjectPreviewService $buildTemplateSubjectPreviewService,
        private readonly BuildTemplateGreetingPreviewService $buildTemplateGreetingPreviewService,
        private readonly BuildTemplateTongHopTablePreviewService $buildTemplateTongHopTablePreviewService,
        private readonly BuildTemplateKhoanNppTablePreviewService $buildTemplateKhoanNppTablePreviewService,
        private readonly BuildTemplateCamCaTablePreviewService $buildTemplateCamCaTablePreviewService,
        private readonly BuildTemplateKeyAccountTablePreviewService $buildTemplateKeyAccountTablePreviewService,
        private readonly BuildTemplatePreviewSampleService $buildTemplatePreviewSampleService,
        private readonly BuildMailTemplateCanvasCompositionService $buildMailTemplateCanvasCompositionService,
        private readonly BuildTemplatePartVersionOverviewService $buildTemplatePartVersionOverviewService,
        private readonly BuildTemplateStructureFromCanvasService $buildTemplateStructureFromCanvasService,
        private readonly SyncLegacyMailTemplateToCompositionService $syncLegacyMailTemplateToCompositionService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexPageData(
        bool $canManageTemplates,
        ?int $selectedPreviewBatchId = null,
        ?int $selectedPreviewRecordId = null,
    ): array
    {
        $this->syncLegacyMailTemplateToCompositionService->syncAll();

        $resolvedPreviewBatchId = $this->buildTemplatePreviewSampleService->resolveSelectedBatchId($selectedPreviewBatchId);
        $builderTemplate = $this->buildBuilderTemplate();
        $selectedTemplate = $builderTemplate === null
            ? null
            : MailTemplate::query()->find($builderTemplate['id']);
        $subjectPreview = $this->buildTemplateSubjectPreviewService->build($selectedTemplate, $resolvedPreviewBatchId, $selectedPreviewRecordId);
        $greetingPreview = $this->buildTemplateGreetingPreviewService->build($selectedTemplate, $resolvedPreviewBatchId, $selectedPreviewRecordId);
        $tongHopPreview = $this->buildTemplateTongHopTablePreviewService->build($selectedTemplate, $resolvedPreviewBatchId, $selectedPreviewRecordId);
        $khoanNppPreview = $this->buildTemplateKhoanNppTablePreviewService->build($selectedTemplate, $resolvedPreviewBatchId, $selectedPreviewRecordId);
        $camCaPreview = $this->buildTemplateCamCaTablePreviewService->build($selectedTemplate, $resolvedPreviewBatchId, $selectedPreviewRecordId);
        $keyAccountPreview = $this->buildTemplateKeyAccountTablePreviewService->build($selectedTemplate, $resolvedPreviewBatchId, $selectedPreviewRecordId);
        $resolvedPreviewRecordId = $subjectPreview['sample']['recordId']
            ?? $greetingPreview['sample']['recordId']
            ?? $tongHopPreview['sample']['recordId']
            ?? $khoanNppPreview['sample']['recordId']
            ?? $camCaPreview['sample']['recordId']
            ?? $keyAccountPreview['sample']['recordId']
            ?? null;

        return [
            'title' => 'Thiết kế mẫu email',
            'description' => 'Thiết kế subject, lời chào và 4 bảng dữ liệu của email chiết khấu theo đúng cấu trúc nghiệp vụ đã được xác nhận.',
            'canManageTemplates' => $canManageTemplates,
            'writeCapabilities' => [
                'Tạo template mới',
                'Chỉnh sửa subject và lời chào',
                'Thiết kế chữ ký đại diện cho Khách thường và Key Account',
                'Thiết kế 4 bảng dữ liệu theo từng sheet nguồn',
                'Đặt một template làm mẫu hoạt động',
            ],
            'readOnlyNotice' => 'Tài khoản hiện tại chỉ được xem cấu trúc template email. Các thao tác tạo, chỉnh sửa và kích hoạt template chỉ mở cho người dùng có quyền quản lý template.',
            'templateParts' => $this->buildTemplateParts(),
            'templateVariables' => $this->templateVariableCatalogService->all(),
            'constraints' => [
                'Canvas email là lớp composition, không phải nơi chứa trực tiếp toàn bộ version của mọi part.',
                'Mỗi part có lifecycle version riêng và policy active riêng theo loại part.',
                'Persistence và read model chính đã chuyển sang schema part versions + canvas bindings; mail_templates chỉ còn giữ compatibility bridge.',
                'Builder, part preview và canvas summary phải đọc từ composition tables, không đọc trực tiếp structure_json của mail_templates như nguồn sự thật chính.',
                'UI phải tách rõ bề mặt quản lý part versions và bề mặt canvas composition, không trộn hai khái niệm trong cùng một màn chỉnh sửa dài.',
                'Preview sẽ dùng dữ liệu aggregate thật từ hệ thống, không dùng dữ liệu minh họa tự dựng.',
                'Subject và lời chào chỉ được dùng các biến đã được xác nhận trong contract.',
            ],
            'templateList' => $this->buildTemplateList(),
            'activeTemplateId' => MailTemplateCanvas::query()
                ->where('is_active', true)
                ->value('legacy_mail_template_id'),
            'builderTemplate' => $builderTemplate,
            'canvasComposition' => $this->buildMailTemplateCanvasCompositionService->build($selectedTemplate),
            'partVersionGroups' => $this->buildTemplatePartVersionOverviewService->build($selectedTemplate),
            'previewBatchOptions' => $this->buildTemplatePreviewSampleService->buildBatchOptions(),
            'selectedPreviewBatchId' => $resolvedPreviewBatchId,
            'previewCustomerOptions' => $this->buildTemplatePreviewSampleService->buildCustomerOptions(null, $resolvedPreviewBatchId),
            'selectedPreviewRecordId' => $resolvedPreviewRecordId,
            'subjectPreview' => $subjectPreview,
            'greetingPreview' => $greetingPreview,
            'tongHopTablePreview' => $tongHopPreview,
            'khoanNppTablePreview' => $khoanNppPreview,
            'camCaTablePreview' => $camCaPreview,
            'keyAccountTablePreview' => $keyAccountPreview,
            'tongHopBindingOptions' => $this->buildTemplateTongHopTablePreviewService->buildBindingOptions($resolvedPreviewBatchId, $resolvedPreviewRecordId),
            'camCaBindingOptions' => $this->buildTemplateCamCaTablePreviewService->buildBindingOptions($resolvedPreviewBatchId, $resolvedPreviewRecordId),
            'keyAccountBindingOptions' => $this->buildTemplateKeyAccountTablePreviewService->buildBindingOptions($resolvedPreviewBatchId, $resolvedPreviewRecordId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplateParts(): array
    {
        return collect($this->templatePartCatalogService->all())
            ->map(fn (array $part): array => [
                'code' => $part['code'],
                'type' => $part['type'],
                'label' => $part['label'],
                'description' => $part['description'],
                'kind' => $part['kind'],
                'sourceSheet' => $part['sourceSheet'],
                'maxActiveVersions' => $part['maxActiveVersions'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplateList(): array
    {
        return MailTemplateCanvas::query()
            ->with(['creator', 'partBindings.templatePart', 'partBindings.templatePartVersion'])
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MailTemplateCanvas $canvas): array => [
                'id' => $canvas->legacy_mail_template_id ?? $canvas->getKey(),
                'name' => $canvas->name,
                'subjectTemplate' => $this->resolveCanvasSubjectTemplate($canvas),
                'isActive' => $canvas->is_active,
                'statusLabel' => $canvas->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động',
                'sectionCount' => $canvas->partBindings->count(),
                'createdBy' => $canvas->creator?->name ?? 'Không xác định',
                'updatedAt' => optional($canvas->updated_at)->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildBuilderTemplate(): ?array
    {
        $mailTemplate = MailTemplate::query()
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (! $mailTemplate) {
            return null;
        }

        $canvas = MailTemplateCanvas::query()
            ->with(['partBindings.templatePart', 'partBindings.templatePartVersion'])
            ->where('legacy_mail_template_id', $mailTemplate->getKey())
            ->first();

        if (! $canvas) {
            return null;
        }

        return [
            'id' => $mailTemplate->getKey(),
            'name' => $canvas->name,
            'subjectTemplate' => $this->resolveCanvasSubjectTemplate($canvas),
            'structure' => $this->buildTemplateStructureFromCanvasService->build($canvas),
        ];
    }

    private function resolveCanvasSubjectTemplate(MailTemplateCanvas $canvas): string
    {
        $subjectBinding = $canvas->partBindings
            ->first(fn ($binding): bool => $binding->templatePart?->type === 'subject');

        return (string) ($subjectBinding?->templatePartVersion?->text_template ?? '');
    }
}
