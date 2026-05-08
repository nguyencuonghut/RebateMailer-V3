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
            'batch_name' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'batch_name.string' => 'Tên batch không hợp lệ.',
            'batch_name.max' => 'Tên batch không được vượt quá 255 ký tự.',
            'file.required' => 'Vui lòng chọn file Excel trước khi tiếp tục.',
            'file.file' => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ chấp nhận file Excel .xlsx.',
        ];
    }
}
