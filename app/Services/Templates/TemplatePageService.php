<?php

namespace App\Services\Templates;

use App\Models\MailTemplate;

class TemplatePageService
{
    public function __construct(
        private readonly TemplateSectionCatalogService $templateSectionCatalogService,
        private readonly TemplateVariableCatalogService $templateVariableCatalogService,
        private readonly BuildTemplateSubjectPreviewService $buildTemplateSubjectPreviewService,
        private readonly BuildTemplateGreetingPreviewService $buildTemplateGreetingPreviewService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexPageData(bool $canManageTemplates): array
    {
        $builderTemplate = $this->buildBuilderTemplate();
        $selectedTemplate = $builderTemplate === null
            ? null
            : MailTemplate::query()->find($builderTemplate['id']);

        return [
            'title' => 'Thiết kế mẫu email',
            'description' => 'Thiết kế subject, lời chào và 4 bảng dữ liệu của email chiết khấu theo đúng cấu trúc nghiệp vụ đã được xác nhận.',
            'currentSlice' => [
                'code' => '2.3-C',
                'label' => 'Thiết kế và preview riêng cho Lời chào',
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
                'Chỉ có một template hoạt động tại một thời điểm.',
                'Template phải hỗ trợ hierarchy cha/con và STT tự tăng.',
                'Preview sẽ dùng dữ liệu aggregate thật từ hệ thống, không dùng dữ liệu minh họa tự dựng.',
                'Subject và lời chào chỉ được dùng các biến đã được xác nhận trong contract.',
            ],
            'templateList' => $this->buildTemplateList(),
            'activeTemplateId' => MailTemplate::query()
                ->where('is_active', true)
                ->value('id'),
            'builderTemplate' => $builderTemplate,
            'subjectPreview' => $this->buildTemplateSubjectPreviewService->build($selectedTemplate),
            'greetingPreview' => $this->buildTemplateGreetingPreviewService->build($selectedTemplate),
            'nextSlice' => [
                'code' => '2.3-D',
                'label' => 'Thiết kế và preview Table Chế độ tháng',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplateParts(): array
    {
        return collect($this->templateSectionCatalogService->all())
            ->map(fn (array $section): array => [
                'code' => str_replace('-table', '', $section['type']),
                'type' => $section['type'],
                'label' => $section['label'],
                'description' => $section['description'],
                'kind' => $section['kind'],
                'sourceSheet' => $section['sourceSheet'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplateList(): array
    {
        return MailTemplate::query()
            ->with(['creator'])
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MailTemplate $mailTemplate): array => [
                'id' => $mailTemplate->getKey(),
                'name' => $mailTemplate->name,
                'subjectTemplate' => $mailTemplate->subject_template,
                'isActive' => $mailTemplate->is_active,
                'statusLabel' => $mailTemplate->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động',
                'sectionCount' => count($mailTemplate->structure_json['sections'] ?? []),
                'createdBy' => $mailTemplate->creator?->name ?? 'Không xác định',
                'updatedAt' => optional($mailTemplate->updated_at)->toIso8601String(),
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

        return [
            'id' => $mailTemplate->getKey(),
            'name' => $mailTemplate->name,
            'subjectTemplate' => $mailTemplate->subject_template,
            'structure' => $mailTemplate->structure_json,
        ];
    }
}
