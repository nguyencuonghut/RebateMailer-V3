<?php

namespace App\Http\Requests\Mail;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('mail.send') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
