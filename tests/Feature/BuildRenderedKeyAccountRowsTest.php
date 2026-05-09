<?php

namespace Tests\Feature;

use App\Services\Templates\BuildRenderedKeyAccountRowsService;
use Tests\TestCase;

class BuildRenderedKeyAccountRowsTest extends TestCase
{
    public function test_it_renders_key_account_hybrid_rows_from_fixed_and_program_data(): void
    {
        $service = new BuildRenderedKeyAccountRowsService;

        $result = $service->build(
            [
                ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'isBold' => false],
                ['content' => 'Chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Chiết khấu theo hóa đơn', 'valueColumn' => 'amount', 'isBold' => true],
                ['content' => '', 'rowType' => 'child-program-loop', 'isBold' => false],
                ['content' => 'Cộng', 'rowType' => 'total', 'isBold' => true],
                ['content' => 'Bằng chữ:', 'rowType' => 'in-words', 'isBold' => false],
            ],
            [
                'Tổng sản lượng' => ['quantity' => '29135', 'supportRate' => '', 'amount' => '', 'defaultValueColumn' => 'quantity'],
                'Chiết khấu theo hóa đơn' => ['quantity' => '', 'supportRate' => '', 'amount' => '42925867', 'defaultValueColumn' => 'amount'],
            ],
            [
                ['content' => 'Chiết khấu tháng sản phẩm cám heo: 380đ/kg', 'quantity' => '28975', 'supportRate' => '380', 'amount' => '11010500'],
                ['content' => 'Hỗ trợ đặc biệt sản phẩm T1120', 'quantity' => '', 'supportRate' => '1270', 'amount' => ''],
                ['content' => '', 'quantity' => '', 'supportRate' => '', 'amount' => ''],
            ],
            '147061500',
            'Một trăm bốn mươi bảy triệu không trăm sáu mươi mốt nghìn năm trăm đồng chẵn.',
        );

        $this->assertSame('', $result['rows'][0]['numbering']);
        $this->assertSame('29135', $result['rows'][0]['quantity']);
        $this->assertSame('I', $result['rows'][1]['numbering']);
        $this->assertSame('42925867', $result['rows'][1]['amount']);
        $this->assertSame('1', $result['rows'][2]['numbering']);
        $this->assertSame('28975', $result['rows'][2]['quantity']);
        $this->assertSame('380', $result['rows'][2]['supportRate']);
        $this->assertSame('11010500', $result['rows'][2]['amount']);
        $this->assertSame('2', $result['rows'][3]['numbering']);
        $this->assertSame('1270', $result['rows'][3]['supportRate']);
        $this->assertSame('Cộng', $result['rows'][4]['content']);
        $this->assertSame('147061500', $result['rows'][4]['amount']);
        $this->assertCount(0, $result['errors']);
    }

    public function test_it_hides_fixed_key_account_rows_when_flag_is_enabled_and_value_is_zero_or_blank(): void
    {
        $service = new BuildRenderedKeyAccountRowsService;

        $result = $service->build(
            [
                ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'quantity', 'hideWhenValueZero' => true, 'isBold' => false],
                ['content' => 'Chiết khấu theo hóa đơn', 'rowType' => 'parent', 'columnKey' => 'Chiết khấu theo hóa đơn', 'valueColumn' => 'amount', 'hideWhenValueZero' => true, 'isBold' => true],
                ['content' => 'Thưởng doanh thu tháng', 'rowType' => 'child-value', 'columnKey' => 'Thưởng doanh thu tháng', 'valueColumn' => 'amount', 'hideWhenValueZero' => false, 'isBold' => false],
            ],
            [
                'Tổng sản lượng' => ['quantity' => '0', 'supportRate' => '', 'amount' => '', 'defaultValueColumn' => 'quantity'],
                'Chiết khấu theo hóa đơn' => ['quantity' => '', 'supportRate' => '', 'amount' => '', 'defaultValueColumn' => 'amount'],
                'Thưởng doanh thu tháng' => ['quantity' => '', 'supportRate' => '', 'amount' => '0', 'defaultValueColumn' => 'amount'],
            ],
            [],
            '0',
            '',
        );

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Thưởng doanh thu tháng', $result['rows'][0]['content']);
        $this->assertSame('1', $result['rows'][0]['numbering']);
        $this->assertCount(0, $result['errors']);
    }

    public function test_it_recovers_key_account_value_column_when_legacy_mapping_points_to_blank_amount(): void
    {
        $service = new BuildRenderedKeyAccountRowsService;

        $result = $service->build(
            [
                ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'amount', 'hideWhenValueZero' => false, 'isBold' => false],
            ],
            [
                'Tổng sản lượng' => ['quantity' => '147300', 'supportRate' => '', 'amount' => '', 'defaultValueColumn' => 'quantity'],
            ],
            [],
            '',
            '',
        );

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Tổng sản lượng', $result['rows'][0]['content']);
        $this->assertSame('', $result['rows'][0]['quantity']);
        $this->assertSame('147300', $result['rows'][0]['amount']);
        $this->assertCount(0, $result['errors']);
    }

    public function test_it_can_render_total_quantity_into_amount_column_when_user_selects_tong_column(): void
    {
        $service = new BuildRenderedKeyAccountRowsService;

        $result = $service->build(
            [
                ['content' => 'Tổng sản lượng', 'rowType' => 'value-row', 'columnKey' => 'Tổng sản lượng', 'valueColumn' => 'amount', 'hideWhenValueZero' => false, 'isBold' => false],
            ],
            [
                'Tổng sản lượng' => ['quantity' => '147300', 'supportRate' => '', 'amount' => '', 'defaultValueColumn' => 'quantity'],
            ],
            [],
            '',
            '',
        );

        $this->assertCount(1, $result['rows']);
        $this->assertSame('Tổng sản lượng', $result['rows'][0]['content']);
        $this->assertSame('', $result['rows'][0]['quantity']);
        $this->assertSame('', $result['rows'][0]['supportRate']);
        $this->assertSame('147300', $result['rows'][0]['amount']);
        $this->assertCount(0, $result['errors']);
    }
}
