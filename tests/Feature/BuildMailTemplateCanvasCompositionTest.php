<?php

namespace Tests\Feature;

use App\Models\MailTemplate;
use App\Models\MailTemplateCanvas;
use App\Services\Templates\BuildMailTemplateCanvasCompositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildMailTemplateCanvasCompositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_canvas_composition_summary_without_embedding_full_part_content(): void
    {
        $mailTemplate = MailTemplate::query()->create([
            'name' => 'Canvas tháng 02',
            'subject_template' => 'Chế độ tháng {{tháng}}',
            'structure_json' => [
                'sections' => [
                    [
                        'type' => 'subject',
                        'kind' => 'text',
                        'content' => 'Chế độ tháng {{tháng}} của khách hàng {{mã & tên khách hàng}}',
                    ],
                    [
                        'type' => 'greeting',
                        'kind' => 'text',
                        'content' => 'Kính gửi {{mã & tên khách hàng}}',
                    ],
                    [
                        'type' => 'tong-hop-table',
                        'kind' => 'table',
                        'sourceSheet' => 'Tổng hợp',
                        'rows' => [
                            ['content' => 'Tổng sản lượng', 'rowType' => 'data'],
                            ['content' => 'Cộng', 'rowType' => 'total'],
                        ],
                    ],
                ],
            ],
        ]);

        $composition = app(BuildMailTemplateCanvasCompositionService::class)->build($mailTemplate);
        $canvas = MailTemplateCanvas::query()->where('legacy_mail_template_id', $mailTemplate->id)->firstOrFail();

        $this->assertNotNull($composition);
        $this->assertSame($canvas->id, $composition['canvasId']);
        $this->assertSame('composition-db', $composition['storageModel']);
        $this->assertCount(7, $composition['partSelections']);
        $this->assertSame('subject', $composition['partSelections'][0]['partType']);
        $this->assertSame(1, $composition['partSelections'][0]['maxActiveVersions']);
        $this->assertTrue($composition['partSelections'][0]['contentSummary']['hasContent']);
        $this->assertGreaterThan(0, $composition['partSelections'][0]['contentSummary']['textLength']);
        $this->assertArrayNotHasKey('content', $composition['partSelections'][0]);
        $this->assertSame('greeting', $composition['partSelections'][1]['partType']);
        $this->assertSame(2, $composition['partSelections'][1]['maxActiveVersions']);
        $this->assertSame('representative-signature', $composition['partSelections'][2]['partType']);
        $this->assertFalse($composition['partSelections'][2]['isConfigured']);
        $this->assertSame('tong-hop-table', $composition['partSelections'][3]['partType']);
        $this->assertSame(2, $composition['partSelections'][3]['contentSummary']['rowCount']);
        $this->assertFalse($composition['partSelections'][4]['isConfigured']);
        $this->assertStringStartsWith('template-part-version-', $composition['partSelections'][3]['selectedVersion']['versionKey']);
    }
}
