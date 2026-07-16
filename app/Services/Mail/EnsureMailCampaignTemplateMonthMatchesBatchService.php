<?php

namespace App\Services\Mail;

use App\Models\ImportBatch;
use App\Models\MailTemplateCanvas;
use Illuminate\Validation\ValidationException;

class EnsureMailCampaignTemplateMonthMatchesBatchService
{
    public function assertForCreation(ImportBatch $importBatch, MailTemplateCanvas $templateCanvas): void
    {
        $message = $this->mismatchMessage($importBatch, $templateCanvas);

        if ($message === null) {
            return;
        }

        throw ValidationException::withMessages([
            'mail_template_canvas_id' => $message,
        ]);
    }

    public function mismatchMessage(ImportBatch $importBatch, MailTemplateCanvas $templateCanvas): ?string
    {
        $batchMonth = $this->resolveBatchMonth($importBatch);
        $templateMonth = $this->resolveTemplateMonth($templateCanvas);

        if ($batchMonth === null || $templateMonth === null || $batchMonth === $templateMonth) {
            return null;
        }

        return sprintf(
            'Template email đang là tháng %s nhưng batch nhập liệu là tháng %s. Vui lòng chọn template cùng tháng hoặc tạo lại chiến dịch sau khi kích hoạt template đúng.',
            $this->formatMonth($templateMonth),
            $this->formatMonth($batchMonth),
        );
    }

    private function resolveBatchMonth(ImportBatch $importBatch): ?string
    {
        foreach ($importBatch->aggregatedRecords()->orderBy('customer_code')->get() as $record) {
            $payload = is_array($record->aggregated_payload) ? $record->aggregated_payload : [];

            foreach (['tongHop', 'khoanNpp', 'camCa', 'keyAccount'] as $sourceKey) {
                $month = $this->normalizeMonth((string) data_get($payload, $sourceKey.'.month', ''));

                if ($month !== null) {
                    return $month;
                }
            }
        }

        return $this->normalizeMonth(implode(' ', [
            (string) $importBatch->name,
            (string) $importBatch->batch_code,
            (string) $importBatch->original_file_name,
        ]));
    }

    private function resolveTemplateMonth(MailTemplateCanvas $templateCanvas): ?string
    {
        $templateCanvas->loadMissing('legacyMailTemplate');

        return $this->normalizeMonth(implode(' ', [
            (string) $templateCanvas->name,
            (string) ($templateCanvas->legacyMailTemplate?->name ?? ''),
        ]));
    }

    private function normalizeMonth(string $value): ?string
    {
        if (preg_match('/(?<!\d)(\d{1,2})\s*[-.\/]\s*(20\d{2})(?!\d)/u', $value, $matches) !== 1) {
            return null;
        }

        $month = (int) $matches[1];

        if ($month < 1 || $month > 12) {
            return null;
        }

        return sprintf('%04d-%02d', (int) $matches[2], $month);
    }

    private function formatMonth(string $normalizedMonth): string
    {
        [$year, $month] = explode('-', $normalizedMonth, 2);

        return sprintf('%s-%s', $month, $year);
    }
}
