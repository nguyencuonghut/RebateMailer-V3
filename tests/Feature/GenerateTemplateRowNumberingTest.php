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
}
