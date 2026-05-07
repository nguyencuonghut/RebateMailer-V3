<?php

namespace App\Http\Requests\Templates;

use App\Services\Templates\TemplatePartCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailTemplateCanvasCompositionRequest extends FormRequest
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
            'partTypes' => ['required', 'array', 'min:2'],
            'partTypes.*' => ['required', 'string', Rule::in($templatePartCatalogService->allowedTypes())],
        ];
    }
}
