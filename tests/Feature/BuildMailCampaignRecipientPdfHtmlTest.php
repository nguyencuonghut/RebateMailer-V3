<?php

namespace Tests\Feature;

use App\Services\Mail\BuildMailCampaignRecipientPdfHtmlService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildMailCampaignRecipientPdfHtmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_service_can_render_pdf_html_from_preview_and_signature_snapshot(): void
    {
        $html = app(BuildMailCampaignRecipientPdfHtmlService::class)->build([
            'subjectLine' => 'Chế độ tháng 03.2026 - 90300 - Công ty A',
            'bodyHtml' => null,
            'preview' => [
                'subject' => [
                    'renderedText' => 'Chế độ tháng 03.2026 - 90300 - Công ty A',
                ],
                'greeting' => [
                    'renderedText' => "Kính gửi 90300 - Công ty A,\nĐịa chỉ: Địa chỉ A",
                ],
                'tables' => [
                    [
                        'type' => 'tong-hop-table',
                        'title' => 'Chế độ tháng 03.2026',
                        'rows' => [
                            [
                                'numbering' => 'I',
                                'content' => 'Chiết khấu theo hóa đơn',
                                'value' => '789000',
                                'fontWeight' => 'bold',
                                'rowType' => 'parent',
                            ],
                            [
                                'numbering' => '1',
                                'content' => 'Bằng chữ:',
                                'value' => 'Bảy trăm tám mươi chín nghìn đồng',
                                'fontWeight' => 'regular',
                                'rowType' => 'text',
                                'columnKey' => 'Bằng chữ',
                            ],
                        ],
                        'errors' => [],
                    ],
                ],
                'errors' => [],
            ],
            'signature' => [
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,ZmFrZS1zaWduYXR1cmU=',
                'representativeRole' => 'Trưởng ban tài chính',
                'representativeName' => 'Nguyễn Văn A',
            ],
        ]);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Rebate Mailer', $html);
        $this->assertStringContainsString('font-family: DejaVu Sans, Arial, Helvetica, sans-serif;', $html);
        $this->assertStringContainsString('Chế độ tháng 03.2026 - 90300 - Công ty A', $html);
        $this->assertStringContainsString('Kính gửi 90300 - Công ty A', $html);
        $this->assertStringContainsString('Chiết khấu theo hóa đơn', $html);
        $this->assertStringContainsString('Bảy trăm tám mươi chín nghìn đồng', $html);
        $this->assertStringContainsString('Đại diện công ty', $html);
        $this->assertStringContainsString('Trưởng ban tài chính', $html);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringNotContainsString('Từ chiến dịch:', $html);
    }
}
