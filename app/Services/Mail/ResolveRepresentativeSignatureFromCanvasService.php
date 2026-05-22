<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use App\Models\TemplatePartVersion;
use RuntimeException;

class ResolveRepresentativeSignatureFromCanvasService
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(MailCampaign $campaign, string $customerType): array
    {
        $canvas = $campaign->templateCanvas()
            ->with(['partBindings.templatePart', 'partBindings.templatePartVersion'])
            ->first();

        $binding = $canvas?->partBindings
            ->first(fn ($item): bool => $item->templatePart?->type === 'representative-signature');

        $version = $binding?->templatePartVersion;

        if (! $version instanceof TemplatePartVersion) {
            throw new RuntimeException('Chiến dịch chưa cấu hình part chữ ký đại diện trong canvas email.');
        }

        $blockKey = $customerType === 'Key Account'
            ? 'keyAccountCustomer'
            : 'normalCustomer';

        $block = $version->structure_json['blocks'][$blockKey] ?? null;

        if (! is_array($block)) {
            throw new RuntimeException(sprintf(
                'Thiếu cấu hình chữ ký đại diện cho loại khách hàng "%s".',
                $customerType,
            ));
        }

        return $block;
    }
}
