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
        ?int $selectedTongHopPreviewRecordId = null,
        ?int $selectedKhoanNppPreviewRecordId = null,
    ): array
    {
        $this->syncLegacyMailTemplateToCompositionService->syncAll();

        $builderTemplate = $this->buildBuilderTemplate();
        $selectedTemplate = $builderTemplate === null
            ? null
            : MailTemplate::query()->find($builderTemplate['id']);
        $tongHopPreview = $this->buildTemplateTongHopTablePreviewService->build($selectedTemplate, $selectedTongHopPreviewRecordId);
        $khoanNppPreview = $this->buildTemplateKhoanNppTablePreviewService->build($selectedTemplate, $selectedKhoanNppPreviewRecordId);

        return [
            'title' => 'Thiết kế mẫu email',
            'description' => 'Thiết kế subject, lời chào và 4 bảng dữ liệu của email chiết khấu theo đúng cấu trúc nghiệp vụ đã được xác nhận.',
            'currentSlice' => [
                'code' => '2.3-E',
                'label' => 'Preview Table Chương trình khoán đặc biệt từ sheet Khoán NPP',
            ],
            'canManageTemplates' => $canManageTemplates,
            'writeCapabilities' => [
                'Tạo template mới',
                'Chỉnh sửa subject và lời chào',
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
            'subjectPreview' => $this->buildTemplateSubjectPreviewService->build($selectedTemplate),
            'greetingPreview' => $this->buildTemplateGreetingPreviewService->build($selectedTemplate),
            'tongHopTablePreview' => $tongHopPreview,
            'tongHopPreviewCustomers' => $this->buildTemplatePreviewSampleService->buildCustomerOptions('tongHop'),
            'selectedTongHopPreviewRecordId' => $tongHopPreview['sample']['recordId'] ?? null,
            'khoanNppTablePreview' => $khoanNppPreview,
            'khoanNppPreviewCustomers' => $this->buildTemplatePreviewSampleService->buildCustomerOptions('khoanNpp'),
            'selectedKhoanNppPreviewRecordId' => $khoanNppPreview['sample']['recordId'] ?? null,
            'tongHopBindingOptions' => $this->buildTemplateTongHopTablePreviewService->buildBindingOptions($selectedTongHopPreviewRecordId),
            'nextSlice' => [
                'code' => '2.3-F',
                'label' => 'Preview Table Chiết khấu cám cá từ sheet Cám cá',
            ],
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
