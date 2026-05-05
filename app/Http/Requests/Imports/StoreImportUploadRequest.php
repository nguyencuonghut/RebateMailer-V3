<?php

namespace App\Http\Requests\Imports;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportUploadRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn file Excel trước khi tiếp tục.',
            'file.file' => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ chấp nhận file Excel .xlsx.',
        ];
    }
}
