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
                    'renderedHtml' => 'Kính gửi <strong>90300 - Công ty A,</strong><br>Địa chỉ: <strong>Địa chỉ A</strong>',
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
        $this->assertStringContainsString('font-family: DejaVu Sans, Arial, Helvetica, sans-serif;', $html);
        $this->assertStringContainsString('Kính gửi <strong>90300 - Công ty A,</strong><br>Địa chỉ: <strong>Địa chỉ A</strong>', $html);
        $this->assertStringContainsString('Kính gửi <strong>90300 - Công ty A,</strong><br>Địa chỉ: <strong>Địa chỉ A</strong>', $html);
        $this->assertStringContainsString('Chiết khấu theo hóa đơn', $html);
        $this->assertStringContainsString('colspan="2"', $html);
        $this->assertStringContainsString('Bảy trăm tám mươi chín nghìn đồng', $html);
        $this->assertMatchesRegularExpression('/Bằng chữ:\s+Bảy trăm tám mươi chín nghìn đồng/u', $html);
        $this->assertStringContainsString('Đại diện công ty', $html);
        $this->assertStringContainsString('Trưởng ban tài chính', $html);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringNotContainsString('Từ chiến dịch:', $html);
        $this->assertStringNotContainsString('Rebate Mailer', $html);
        $this->assertStringNotContainsString('<h1', $html);
    }

    public function test_service_normalizes_snapshot_body_html_for_print_friendly_pdf(): void
    {
        $html = app(BuildMailCampaignRecipientPdfHtmlService::class)->build([
            'subjectLine' => 'Subject',
            'bodyHtml' => '<div style="font-size:15px; line-height:1.6;">Kính gửi A</div><div style="margin-top:28px;"><h2 style="font-size:20px;">Bảng</h2><table><tr><td style="padding:12px 14px; font-size:14px;">1</td></tr></table></div>',
            'preview' => null,
            'signature' => [
                'title' => null,
                'signatureImageDataUrl' => null,
                'representativeRole' => null,
                'representativeName' => null,
            ],
        ]);

        $this->assertStringContainsString('font-size:12px; line-height:1.3;">Kính gửi A', $html);
        $this->assertStringContainsString('margin-top:12px;', $html);
        $this->assertStringContainsString('font-size:14px;">Bảng', $html);
        $this->assertStringContainsString('padding:5px 7px; font-size:10px;">1', $html);
    }
}
