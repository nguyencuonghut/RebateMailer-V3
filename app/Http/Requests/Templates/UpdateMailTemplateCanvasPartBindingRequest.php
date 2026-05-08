<?php

namespace App\Http\Requests\Templates;

use App\Services\Templates\TemplatePartCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailTemplateCanvasPartBindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(TemplatePartCatalogService $templatePartCatalogService): array
    {
        return [
            'partType' => ['required', 'string', Rule::in($templatePartCatalogService->allowedTypes())],
            'templatePartVersionId' => ['required', 'integer', 'min:1'],
        ];
    }
}
