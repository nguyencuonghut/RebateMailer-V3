<?php

namespace Tests\Feature;

use App\Services\Mail\BuildMailCampaignRecipientPdfHtmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildMailCampaignRecipientPdfHtmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_html_renderer_reuses_mail_body_and_renders_representative_signature(): void
    {
        $html = app(BuildMailCampaignRecipientPdfHtmlService::class)->build(
            preview: [
                'recipient' => [
                    'customerFullName' => '90300 - Công ty A',
                    'recipientEmail' => 'send@example.com',
                ],
                'subject' => [
                    'renderedText' => 'Thư chiết khấu tháng 6',
                ],
                'greeting' => [
                    'renderedText' => 'Kính gửi Quý khách',
                ],
                'tables' => [
                    [
                        'type' => 'tong-hop-table',
                        'label' => 'Bảng chế độ tháng',
                        'title' => 'Chế độ tháng 06.2026',
                        'rows' => [
                            [
                                'numbering' => 'I',
                                'content' => 'Tổng cộng',
                                'value' => '123456',
                                'rowType' => 'data',
                                'fontWeight' => 'bold',
                            ],
                        ],
                    ],
                ],
                'errors' => [],
            ],
            signatureSnapshot: [
                'partType' => 'representative-signature',
                'customerType' => 'Khách thường',
                'title' => 'Đại diện công ty',
                'signatureImageDataUrl' => 'data:image/png;base64,bm9ybWFsLXNpZw==',
                'representativeRole' => 'Giám đốc kinh doanh',
                'representativeName' => 'Nguyễn Văn A',
            ],
            campaignName: 'Chiến dịch tháng 6',
        );

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Thư chiết khấu tháng 6', $html);
        $this->assertStringContainsString('Kính gửi Quý khách', $html);
        $this->assertStringContainsString('Chế độ tháng 06.2026', $html);
        $this->assertStringContainsString('Đại diện công ty', $html);
        $this->assertStringContainsString('Giám đốc kinh doanh', $html);
        $this->assertStringContainsString('Nguyễn Văn A', $html);
        $this->assertStringContainsString('data:image/png;base64,bm9ybWFsLXNpZw==', $html);
        $this->assertStringContainsString('text-align:right', $html);
        $this->assertStringNotContainsString('Chiến dịch gửi mail', $html);
        $this->assertStringNotContainsString('Từ chiến dịch:', $html);
    }
}
