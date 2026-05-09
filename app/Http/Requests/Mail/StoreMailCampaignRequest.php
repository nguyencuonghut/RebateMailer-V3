<?php

namespace App\Http\Requests\Mail;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'import_batch_id' => [
                'required',
                'integer',
                Rule::exists('import_batches', 'id'),
            ],
            'mail_template_canvas_id' => [
                'required',
                'integer',
                Rule::exists('mail_template_canvases', 'id'),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên chiến dịch',
            'import_batch_id' => 'batch nhập liệu',
            'mail_template_canvas_id' => 'template email',
            'notes' => 'ghi chú',
        ];
    }
}
