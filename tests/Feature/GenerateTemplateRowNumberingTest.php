<?php

namespace Tests\Feature;

use App\Services\Templates\GenerateTemplateRowNumberingService;
use Tests\TestCase;

class GenerateTemplateRowNumberingTest extends TestCase
{
    public function test_it_generates_roman_numbers_for_parent_rows_and_arabic_for_child_rows(): void
    {
        $service = new GenerateTemplateRowNumberingService;

        $result = $service->generate([
            ['content' => 'Cha 1', 'indentLevel' => 0],
            ['content' => 'Con 1', 'indentLevel' => 1],
            ['content' => 'Con 2', 'indentLevel' => 1],
            ['content' => 'Cha 2', 'indentLevel' => 0],
            ['content' => 'Con 1 của cha 2', 'indentLevel' => 1],
        ]);

        $this->assertSame('I', $result[0]['numbering']);
        $this->assertSame('parent', $result[0]['styleRole']);
        $this->assertSame('bold', $result[0]['fontWeight']);
        $this->assertSame('1', $result[1]['numbering']);
        $this->assertSame('child', $result[1]['styleRole']);
        $this->assertSame('regular', $result[1]['fontWeight']);
        $this->assertSame('2', $result[2]['numbering']);
        $this->assertSame('II', $result[3]['numbering']);
        $this->assertSame('parent', $result[3]['styleRole']);
        $this->assertSame('1', $result[4]['numbering']);
    }

    public function test_it_recalculates_numbering_after_indent_changes_and_keeps_deeper_levels_deterministic(): void
    {
        $service = new GenerateTemplateRowNumberingService;

        $result = $service->generate([
            ['content' => 'Cha 1', 'indentLevel' => 0],
            ['content' => 'Con 1', 'indentLevel' => 1],
            ['content' => 'Con cấp 2', 'indentLevel' => 2],
            ['content' => 'Con 2', 'indentLevel' => 1],
            ['content' => 'Cha 2', 'indentLevel' => 0],
        ]);

        $this->assertSame('I', $result[0]['numbering']);
        $this->assertSame('1', $result[1]['numbering']);
        $this->assertSame('1', $result[2]['numbering']);
        $this->assertSame('2', $result[3]['numbering']);
        $this->assertSame('II', $result[4]['numbering']);
    }

    public function test_it_supports_semantic_row_types_for_tong_hop_rows(): void
    {
        $service = new GenerateTemplateRowNumberingService;

        $result = $service->generate([
            ['content' => 'Tổng sản lượng', 'rowType' => 'blank', 'columnKey' => 'Tổng sản lượng'],
            ['content' => 'Tiền chiết khấu theo Hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn'],
            ['content' => 'Thưởng cam kết tháng', 'rowType' => 'child', 'columnKey' => 'Thưởng cam kết tháng'],
            ['content' => 'Cộng', 'rowType' => 'total', 'columnKey' => 'Cộng'],
            ['content' => 'Bằng chữ:', 'rowType' => 'text', 'columnKey' => 'Bằng chữ'],
        ]);

        $this->assertSame('', $result[0]['numbering']);
        $this->assertSame('neutral', $result[0]['styleRole']);
        $this->assertSame('I', $result[1]['numbering']);
        $this->assertSame('parent', $result[1]['styleRole']);
        $this->assertSame('1', $result[2]['numbering']);
        $this->assertSame('child', $result[2]['styleRole']);
        $this->assertSame('', $result[3]['numbering']);
        $this->assertSame('bold', $result[3]['fontWeight']);
        $this->assertSame('', $result[4]['numbering']);
        $this->assertSame('regular', $result[4]['fontWeight']);
    }

    public function test_it_does_not_assign_numbering_for_top_level_data_rows(): void
    {
        $service = new GenerateTemplateRowNumberingService;

        $result = $service->generate([
            ['content' => 'Tổng sản lượng (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Tổng sản lượng (gồm cám thủy sản)'],
            ['content' => 'Doanh thu (gồm cám thủy sản)', 'rowType' => 'data', 'columnKey' => 'Doanh thu (gồm cám thủy sản)'],
            ['content' => 'Tiền chiết khấu theo Hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn'],
            ['content' => 'Thưởng cam kết tháng', 'rowType' => 'child', 'columnKey' => 'Thưởng cam kết tháng'],
        ]);

        $this->assertSame('', $result[0]['numbering']);
        $this->assertSame('neutral', $result[0]['styleRole']);
        $this->assertSame('', $result[1]['numbering']);
        $this->assertSame('neutral', $result[1]['styleRole']);
        $this->assertSame('I', $result[2]['numbering']);
        $this->assertSame('1', $result[3]['numbering']);
    }

    public function test_it_treats_khoan_npp_semantic_rows_as_non_numbered_builder_rows(): void
    {
        $service = new GenerateTemplateRowNumberingService;

        $result = $service->generate([
            ['content' => '', 'rowType' => 'program-loop'],
            ['content' => '', 'rowType' => 'blank'],
            ['content' => 'Cộng', 'rowType' => 'total'],
            ['content' => 'Bằng chữ:', 'rowType' => 'in-words'],
        ]);

        $this->assertSame('', $result[0]['numbering']);
        $this->assertSame('neutral', $result[0]['styleRole']);
        $this->assertSame('', $result[1]['numbering']);
        $this->assertSame('', $result[2]['numbering']);
        $this->assertSame('bold', $result[2]['fontWeight']);
        $this->assertSame('', $result[3]['numbering']);
    }

    public function test_it_supports_cam_ca_semantic_rows_for_builder_numbering(): void
    {
        $service = new GenerateTemplateRowNumberingService;

        $result = $service->generate([
            ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng'],
            ['content' => 'Tiền chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Tiền chiết khấu theo Hóa đơn'],
            ['content' => 'Thưởng sản lượng tháng 03.2026', 'rowType' => 'child-value', 'columnKey' => 'Thưởng sản lượng tháng 03.2026'],
            ['content' => '', 'rowType' => 'child-program-loop'],
            ['content' => 'Cộng', 'rowType' => 'total'],
        ]);

        $this->assertSame('', $result[0]['numbering']);
        $this->assertSame('I', $result[1]['numbering']);
        $this->assertSame('1', $result[2]['numbering']);
        $this->assertSame('2', $result[3]['numbering']);
        $this->assertSame('', $result[4]['numbering']);
    }
}
