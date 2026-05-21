<?php

namespace App\Services\Mail;

use App\Models\MailCampaign;
use RuntimeException;

class ResolveRepresentativeSignatureFromCanvasService
{
    /**
     * @return array<string, string|null>
     */
    public function resolve(MailCampaign $campaign, string $customerType): array
    {
        $canvas = $campaign->templateCanvas()
            ->with(['partBindings.templatePart', 'partBindings.templatePartVersion'])
            ->first();

        if (! $canvas) {
            throw new RuntimeException('Chiến dịch chưa gắn template canvas để resolve chữ ký đại diện.');
        }

        $binding = $canvas->partBindings
            ->first(fn ($item): bool => $item->templatePart?->type === 'representative-signature');

        $structure = $binding?->templatePartVersion?->structure_json;

        if (! is_array($structure)) {
            throw new RuntimeException('Template canvas chưa cấu hình chữ ký đại diện.');
        }

        $blocks = $structure['blocks'] ?? null;

        if (! is_array($blocks)) {
            throw new RuntimeException('Cấu hình chữ ký đại diện hiện không hợp lệ.');
        }

        $block = $blocks[$this->resolveBlockKey($customerType)] ?? null;

        if (! is_array($block)) {
            throw new RuntimeException(sprintf('Template canvas thiếu block chữ ký cho loại khách "%s".', $customerType));
        }

        return [
            'partType' => 'representative-signature',
            'customerType' => $customerType,
            'title' => trim((string) ($block['title'] ?? '')) ?: 'Đại diện công ty',
            'signatureImageDataUrl' => $this->normalizeOptionalString($block['signatureImageDataUrl'] ?? null),
            'representativeRole' => $this->normalizeOptionalString($block['representativeRole'] ?? null),
            'representativeName' => $this->normalizeOptionalString($block['representativeName'] ?? null),
        ];
    }

    private function resolveBlockKey(string $customerType): string
    {
        return match (trim($customerType)) {
            'Khách thường' => 'normalCustomer',
            'Key Account' => 'keyAccountCustomer',
            default => throw new RuntimeException(sprintf('Không hỗ trợ loại khách "%s" để resolve chữ ký đại diện.', $customerType)),
        };
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
