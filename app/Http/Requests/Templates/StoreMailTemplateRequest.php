<?php

namespace App\Http\Requests\Templates;

use App\Services\Templates\ValidateTemplateVariablesService;
use Illuminate\Foundation\Http\FormRequest;

class StoreMailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(ValidateTemplateVariablesService $validateTemplateVariablesService): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject_template' => [
                'nullable',
                'string',
                'max:1000',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateVariables(
                    $validateTemplateVariablesService,
                    $value,
                    $fail,
                ),
            ],
            'greeting_template' => [
                'nullable',
                'string',
                'max:5000',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validateVariables(
                    $validateTemplateVariablesService,
                    $value,
                    $fail,
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên template',
            'subject_template' => 'subject template',
            'greeting_template' => 'lời chào',
        ];
    }

    private function validateVariables(
        ValidateTemplateVariablesService $validateTemplateVariablesService,
        mixed $value,
        \Closure $fail,
    ): void {
        if (! is_string($value)) {
            return;
        }

        if (! $validateTemplateVariablesService->passes($value)) {
            $fail($validateTemplateVariablesService->message());
        }
    }
}
