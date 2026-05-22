<?php

namespace App\Services\Templates;

use App\Models\MailCampaign;
use App\Models\MailCampaignRecipient;
use App\Models\MailTemplate;
use Illuminate\Validation\ValidationException;

class ResolveMailTemplateEditLockService
{
    /**
     * @return array{
     *     isLocked: bool,
     *     reason: string|null,
     *     sentCampaignCount: int,
     *     sentRecipientCount: int
     * }
     */
    public function resolve(MailTemplate $mailTemplate): array
    {
        $sentRecipientCount = MailCampaignRecipient::query()
            ->where('delivery_status', 'sent')
            ->whereHas('campaign', fn ($query) => $query->whereHas(
                'templateCanvas',
                fn ($canvasQuery) => $canvasQuery->where('legacy_mail_template_id', $mailTemplate->getKey()),
            ))
            ->count();

        $sentCampaignCount = $sentRecipientCount === 0
            ? 0
            : MailCampaign::query()
                ->whereHas(
                    'templateCanvas',
                    fn ($query) => $query->where('legacy_mail_template_id', $mailTemplate->getKey()),
                )
                ->whereHas('recipients', fn ($query) => $query->where('delivery_status', 'sent'))
                ->count();

        $isLocked = $sentRecipientCount > 0;

        return [
            'isLocked' => $isLocked,
            'reason' => $isLocked
                ? sprintf(
                    'Template này đã được dùng để gửi %d mail ở %d chiến dịch nên không thể chỉnh sửa nữa. Hãy tạo template mới nếu cần thay đổi nội dung.',
                    $sentRecipientCount,
                    $sentCampaignCount,
                )
                : null,
            'sentCampaignCount' => $sentCampaignCount,
            'sentRecipientCount' => $sentRecipientCount,
        ];
    }

    public function assertEditable(MailTemplate $mailTemplate): void
    {
        $state = $this->resolve($mailTemplate);

        if (! $state['isLocked']) {
            return;
        }

        throw ValidationException::withMessages([
            'template' => $state['reason'],
        ]);
    }
}
