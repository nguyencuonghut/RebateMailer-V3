<?php

namespace Tests\Feature;

use App\Services\Templates\BuildRenderedTongHopRowsService;
use Tests\TestCase;

class BuildRenderedTongHopRowsTest extends TestCase
{
    public function test_it_hides_rows_flagged_hide_when_value_zero_for_zero_and_blank_values(): void
    {
        $result = app(BuildRenderedTongHopRowsService::class)->build([
            [
                'content' => 'Chiết khấu cám cá',
                'rowType' => 'child',
                'columnKey' => 'Chiết khấu cám cá',
                'hideWhenValueZero' => true,
                'numbering' => '1',
                'indentLevel' => 1,
                'fontWeight' => 'regular',
            ],
            [
                'content' => 'Khoán tháng',
                'rowType' => 'child',
                'columnKey' => 'Khoán tháng',
                'hideWhenValueZero' => true,
                'numbering' => '2',
                'indentLevel' => 1,
                'fontWeight' => 'regular',
            ],
            [
                'content' => 'Cộng',
                'rowType' => 'total',
                'columnKey' => 'Cộng',
                'hideWhenValueZero' => false,
                'numbering' => '',
                'indentLevel' => 0,
                'fontWeight' => 'bold',
            ],
        ], [
            'Chiết khấu cám cá' => '0',
            'Khoán tháng' => '',
            'Cộng' => '300000',
        ]);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Cộng', $result['rows'][0]['content']);
        $this->assertSame('300000', $result['rows'][0]['value']);
        $this->assertSame([], $result['errors']);
    }

    public function test_it_keeps_row_and_reports_error_when_mapping_key_is_missing_but_hide_flag_is_off(): void
    {
        $result = app(BuildRenderedTongHopRowsService::class)->build([
            [
                'content' => 'Nội dung không tồn tại',
                'rowType' => 'child',
                'columnKey' => 'Nội dung không tồn tại',
                'hideWhenValueZero' => false,
                'numbering' => '1',
                'indentLevel' => 1,
                'fontWeight' => 'regular',
            ],
        ], []);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('', $result['rows'][0]['value']);
        $this->assertSame(
            'Dòng "Nội dung không tồn tại" chưa tìm thấy dữ liệu tương ứng trong sheet Tổng hợp đã aggregate.',
            $result['errors'][0],
        );
    }
}
