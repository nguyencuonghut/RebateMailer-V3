<?php

namespace App\Services\Mail;

use App\Models\MailCampaignExport;
use App\Models\MailCampaignRecipient;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class GenerateMailCampaignPdfExportService
{
    public function __construct(
        private readonly BuildMailCampaignRecipientPdfPayloadService $buildMailCampaignRecipientPdfPayloadService,
    ) {
    }

    public function generate(MailCampaignExport $export): MailCampaignExport
    {
        $export->loadMissing(['campaign.templateCanvas.legacyMailTemplate']);

        $campaign = $export->campaign;

        if (! $campaign) {
            throw new RuntimeException('Không tìm thấy chiến dịch để tạo export PDF.');
        }

        $export->forceFill([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'failed_at' => null,
        ])->save();

        $tempZipPath = null;

        try {
            $disk = (string) config('mail_campaigns.exports.disk', 'local');
            $directory = trim((string) config('mail_campaigns.exports.directory', 'mail-exports/pdf'), '/');
            $fileName = sprintf('mail-campaign-%d-export-%d.zip', $campaign->id, $export->id);
            $filePath = $directory.'/'.$fileName;

            $tempZipPath = tempnam(sys_get_temp_dir(), 'pdf_export_');

            $zip = new ZipArchive();
            if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Không thể tạo file ZIP tạm để export PDF.');
            }

            $exportedCount = 0;

            $campaign->recipients()
                ->orderBy('customer_code')
                ->lazy(20)
                ->each(function (MailCampaignRecipient $recipient) use ($campaign, $zip, &$exportedCount, $export): void {
                    $payload = $this->buildMailCampaignRecipientPdfPayloadService->build($campaign, $recipient);

                    $html = view('mail.campaign-export-pdf-document', [
                        'documentTitle' => sprintf('%s - %s', $campaign->name, $recipient->customer_code),
                        'pages' => [$payload],
                    ])->render();

                    $pdfBytes = \Barryvdh\DomPDF\Facade\Pdf::setOption(['defaultFont' => 'DejaVu Sans'])
                        ->loadHTML($html)
                        ->output();

                    $safeName = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $recipient->customer_code);
                    $zip->addFromString($safeName.'.pdf', $pdfBytes);

                    unset($pdfBytes, $html, $payload);

                    $exportedCount++;

                    if ($exportedCount % 10 === 0) {
                        $export->forceFill(['exported_recipients' => $exportedCount])->save();
                        gc_collect_cycles();
                    }
                });

            if ($exportedCount === 0) {
                $zip->close();
                throw new RuntimeException('Chiến dịch không có người nhận để tạo file PDF.');
            }

            $zip->close();

            $stream = fopen($tempZipPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Không thể đọc file ZIP tạm sau khi tạo.');
            }
            Storage::disk($disk)->writeStream($filePath, $stream);
            fclose($stream);

            @unlink($tempZipPath);
            $tempZipPath = null;

            $export->forceFill([
                'status' => 'completed',
                'file_disk' => $disk,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'exported_recipients' => $exportedCount,
                'completed_at' => now(),
            ])->save();

        } catch (Throwable $throwable) {
            if ($tempZipPath !== null && file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }

            $export->forceFill([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
                'failed_at' => now(),
                'completed_at' => null,
                'file_disk' => null,
                'file_path' => null,
                'file_name' => null,
                'exported_recipients' => 0,
            ])->save();

            throw $throwable;
        }

        return $export->refresh();
    }
}
