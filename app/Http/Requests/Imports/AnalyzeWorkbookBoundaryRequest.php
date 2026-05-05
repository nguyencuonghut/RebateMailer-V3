<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class AnalyzeWorkbookBoundaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('imports.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'storedPath' => ['required', 'string', 'starts_with:imports/tmp/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'storedPath.required' => 'Không tìm thấy receipt upload để đọc workbook.',
            'storedPath.string' => 'Đường dẫn file tạm không hợp lệ.',
            'storedPath.starts_with' => 'Chỉ được phân tích file upload tạm của module import.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $storedPath = $this->string('storedPath')->toString();

            if ($storedPath === '') {
                return;
            }

            if (! Storage::disk('local')->exists($storedPath)) {
                $validator->errors()->add('storedPath', 'Không tìm thấy file upload tạm để đọc workbook.');
            }
        });
    }
}
