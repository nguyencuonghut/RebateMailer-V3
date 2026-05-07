<?php

namespace App\Http\Requests\Templates;

use App\Services\Templates\TemplateSectionCatalogService;
use App\Services\Templates\ValidateTemplateVariablesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailTemplateStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(
        TemplateSectionCatalogService $templateSectionCatalogService,
        ValidateTemplateVariablesService $validateTemplateVariablesService,
    ): array
    {
        $allowedTypes = $templateSectionCatalogService->allowedTypes();

        return [
            'version' => ['required', 'string', 'max:50'],
            'sections' => ['required', 'array'],
            'sections.*.type' => ['required', 'string', Rule::in($allowedTypes)],
            'sections.*.label' => ['nullable', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string', 'max:1000'],
            'sections.*.kind' => ['nullable', 'string', Rule::in(['text', 'table'])],
            'sections.*.sourceSheet' => ['nullable', 'string', 'max:255'],
            'sections.*.content' => [
                'nullable',
                'string',
                'max:10000',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateTextSectionVariables(
                    $validateTemplateVariablesService,
                    $attribute,
                    $value,
                    $fail,
                ),
            ],
            'sections.*.rows' => ['nullable', 'array'],
            'sections.*.rows.*.content' => ['required_with:sections.*.rows', 'string', 'max:5000'],
            'sections.*.rows.*.indentLevel' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'version' => 'phiên bản structure',
            'sections' => 'danh sách section',
            'sections.*.type' => 'loại section',
            'sections.*.content' => 'nội dung section',
            'sections.*.rows.*.content' => 'nội dung dòng',
        ];
    }

    private function validateTextSectionVariables(
        ValidateTemplateVariablesService $validateTemplateVariablesService,
        string $attribute,
        mixed $value,
        \Closure $fail,
    ): void {
        if (! is_string($value)) {
            return;
        }

        if (! preg_match('/^sections\.(\d+)\.content$/', $attribute, $matches)) {
            return;
        }

        $sectionIndex = (int) $matches[1];
        $sectionType = data_get($this->input('sections'), $sectionIndex.'.type');

        if (! in_array($sectionType, ['subject', 'greeting'], true)) {
            return;
        }

        if (! $validateTemplateVariablesService->passes($value)) {
            $fail($validateTemplateVariablesService->message());
        }
    }
}
