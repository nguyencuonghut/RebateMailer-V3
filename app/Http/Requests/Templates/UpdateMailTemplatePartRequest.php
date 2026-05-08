<?php

namespace App\Http\Requests\Templates;

use App\Services\Templates\TemplatePartCatalogService;
use App\Services\Templates\ValidateTemplateVariablesService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailTemplatePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(
        TemplatePartCatalogService $templatePartCatalogService,
        ValidateTemplateVariablesService $validateTemplateVariablesService,
    ): array {
        return [
            'partType' => ['required', 'string', Rule::in($templatePartCatalogService->allowedTypes())],
            'content' => [
                'nullable',
                'string',
                'max:10000',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateTextPartVariables(
                    $validateTemplateVariablesService,
                    $value,
                    $fail,
                ),
            ],
            'section' => ['nullable', 'array'],
            'section.type' => ['required_with:section', 'string', Rule::in($templatePartCatalogService->allowedTypes())],
            'section.label' => ['nullable', 'string', 'max:255'],
            'section.description' => ['nullable', 'string', 'max:1000'],
            'section.kind' => ['nullable', 'string', Rule::in(['text', 'table'])],
            'section.sourceSheet' => ['nullable', 'string', 'max:255'],
            'section.rows' => ['nullable', 'array'],
            'section.rows.*.content' => ['nullable', 'string', 'max:5000'],
            'section.rows.*.indentLevel' => ['nullable', 'integer'],
            'section.rows.*.rowType' => ['nullable', 'string', Rule::in(['blank', 'parent', 'child', 'data', 'total', 'text', 'program-loop', 'in-words', 'value-row', 'child-value', 'child-program-loop'])],
            'section.rows.*.columnKey' => ['nullable', 'string', 'max:5000'],
            'section.rows.*.hideWhenValueZero' => ['nullable', 'boolean'],
            'section.rows.*.isBold' => ['nullable', 'boolean'],
        ];
    }

    private function validateTextPartVariables(
        ValidateTemplateVariablesService $validateTemplateVariablesService,
        mixed $value,
        \Closure $fail,
    ): void {
        $partType = (string) $this->input('partType');

        if (! in_array($partType, ['subject', 'greeting'], true) || ! is_string($value)) {
            return;
        }

        if (! $validateTemplateVariablesService->passes($value)) {
            $fail($validateTemplateVariablesService->message());
        }
    }
}
