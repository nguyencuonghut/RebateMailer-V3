<?php

namespace Tests\Feature;

use App\Services\Templates\BuildRenderedCamCaRowsService;
use Tests\TestCase;

class BuildRenderedCamCaRowsTest extends TestCase
{
    public function test_it_renders_cam_ca_hybrid_rows_and_hides_zero_or_blank_entries(): void
    {
        $service = new BuildRenderedCamCaRowsService;

        $result = $service->build(
            [
                ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'hideWhenValueZero' => false, 'isBold' => false],
                ['content' => 'Tiền chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn', 'hideWhenValueZero' => false, 'isBold' => true],
                ['content' => 'Thưởng sản lượng tháng 03.2026', 'rowType' => 'child-value', 'columnKey' => 'Thưởng sản lượng tháng 03.2026', 'hideWhenValueZero' => false, 'isBold' => false],
                ['content' => '', 'rowType' => 'child-program-loop', 'hideWhenValueZero' => true, 'isBold' => false],
                ['content' => 'Chiết khấu khác ( không thể hiện trên hóa đơn)', 'rowType' => 'parent', 'columnKey' => 'Chiết khấu khác ( Không thể hiện trên hóa đơn)', 'hideWhenValueZero' => false, 'isBold' => true],
                ['content' => 'Chiết khấu thanh toán', 'rowType' => 'child-value', 'columnKey' => 'Chiết khấu thanh toán', 'hideWhenValueZero' => true, 'isBold' => false],
                ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'isBold' => false],
            ],
            [
                'Tổng sản lượng' => '128500',
                'Tiền chiết khấu theo Hóa đơn' => '199615000',
                'Thưởng sản lượng tháng 03.2026' => '89950000',
                'Chiết khấu khác ( Không thể hiện trên hóa đơn)' => '12850000',
                'Chiết khấu thanh toán' => '0',
            ],
            [
                ['content' => 'Chiết khấu quý 1.2026 sản phẩm 9113 mức 250đ/kg', 'amount' => '51200000'],
                ['content' => '', 'amount' => '0'],
            ],
            '212465000',
            'Hai trăm mười hai triệu, bốn trăm sáu mươi lăm nghìn đồng chẵn.',
        );

        $this->assertSame('Tổng sản lượng', $result['rows'][0]['content']);
        $this->assertSame('', $result['rows'][0]['numbering']);
        $this->assertSame('I', $result['rows'][1]['numbering']);
        $this->assertSame('1', $result['rows'][2]['numbering']);
        $this->assertSame('2', $result['rows'][3]['numbering']);
        $this->assertSame('Chiết khấu quý 1.2026 sản phẩm 9113 mức 250đ/kg', $result['rows'][3]['content']);
        $this->assertSame('II', $result['rows'][4]['numbering']);
        $this->assertSame('Cộng', $result['rows'][5]['content']);
        $this->assertSame('212465000', $result['rows'][5]['value']);
        $this->assertSame('Bằng chữ:', $result['rows'][6]['content']);
        $this->assertCount(0, $result['errors']);
    }
}
