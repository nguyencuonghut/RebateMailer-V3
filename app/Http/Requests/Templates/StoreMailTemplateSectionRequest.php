<?php

namespace App\Http\Requests\Templates;

use App\Services\Templates\TemplateSectionCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailTemplateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(TemplateSectionCatalogService $templateSectionCatalogService): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in($templateSectionCatalogService->allowedTypes()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'loại section',
        ];
    }
}
