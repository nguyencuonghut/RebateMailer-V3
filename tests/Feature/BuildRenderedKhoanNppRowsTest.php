<?php

namespace Tests\Feature;

use App\Services\Templates\BuildRenderedKhoanNppRowsService;
use Tests\TestCase;

class BuildRenderedKhoanNppRowsTest extends TestCase
{
    public function test_it_renders_only_meaningful_program_items_and_auto_increments_stt(): void
    {
        $service = new BuildRenderedKhoanNppRowsService;

        $result = $service->build([
            ['content' => '', 'rowType' => 'program-loop', 'isBold' => false],
            ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
            ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'isBold' => false],
        ], [
            'programItems' => [
                ['content' => 'CT 1', 'quantity' => '19000', 'supportRate' => '200', 'amount' => '3800000'],
                ['content' => '', 'quantity' => '', 'supportRate' => '', 'amount' => '0'],
                ['content' => 'CT 3', 'quantity' => '', 'supportRate' => '', 'amount' => '3000000'],
            ],
            'grandTotal' => '6800000',
            'totalInWords' => 'Sáu triệu tám trăm nghìn đồng chẵn.',
        ]);

        $this->assertCount(4, $result['rows']);
        $this->assertSame('1', $result['rows'][0]['numbering']);
        $this->assertSame('CT 1', $result['rows'][0]['content']);
        $this->assertSame('2', $result['rows'][1]['numbering']);
        $this->assertSame('CT 3', $result['rows'][1]['content']);
        $this->assertSame('', $result['rows'][2]['numbering']);
        $this->assertSame('Cộng', $result['rows'][2]['content']);
        $this->assertSame('6800000', $result['rows'][2]['amount']);
        $this->assertSame('Bằng chữ:', $result['rows'][3]['content']);
        $this->assertSame('Sáu triệu tám trăm nghìn đồng chẵn.', $result['rows'][3]['amount']);
    }
}
