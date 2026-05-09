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

    public function test_it_recalculates_child_numbering_after_hidden_rows_are_removed(): void
    {
        $result = app(BuildRenderedTongHopRowsService::class)->build([
            [
                'content' => 'Mục cha I',
                'rowType' => 'parent',
                'numbering' => 'I',
                'indentLevel' => 0,
                'hideWhenValueZero' => false,
                'fontWeight' => 'bold',
            ],
            [
                'content' => 'Con 1',
                'rowType' => 'child',
                'columnKey' => 'Con 1',
                'numbering' => '1',
                'indentLevel' => 1,
                'hideWhenValueZero' => false,
                'fontWeight' => 'regular',
            ],
            [
                'content' => 'Con 2 bị ẩn',
                'rowType' => 'child',
                'columnKey' => 'Con 2 bị ẩn',
                'numbering' => '2',
                'indentLevel' => 1,
                'hideWhenValueZero' => true,
                'fontWeight' => 'regular',
            ],
            [
                'content' => 'Con 3 còn lại',
                'rowType' => 'child',
                'columnKey' => 'Con 3 còn lại',
                'numbering' => '3',
                'indentLevel' => 1,
                'hideWhenValueZero' => false,
                'fontWeight' => 'regular',
            ],
        ], [
            'Con 1' => '100',
            'Con 2 bị ẩn' => '0',
            'Con 3 còn lại' => '250',
        ]);

        $this->assertCount(3, $result['rows']);
        $this->assertSame('I', $result['rows'][0]['numbering']);
        $this->assertSame('1', $result['rows'][1]['numbering']);
        $this->assertSame('2', $result['rows'][2]['numbering']);
        $this->assertSame('Con 3 còn lại', $result['rows'][2]['content']);
    }
}
