<?php

namespace App\Services\Templates;

class GenerateTemplateRowNumberingService
{
    /**
     * @param  array<int, array{content: string, indentLevel?: int}>  $rows
     * @return array<int, array{content: string, indentLevel: int, numbering: string, styleRole: string, fontWeight: string}>
     */
    public function generate(array $rows): array
    {
        $counters = [];

        return array_map(function (array $row) use (&$counters): array {
            $indentLevel = max(0, (int) ($row['indentLevel'] ?? 0));

            foreach (array_keys($counters) as $level) {
                if ($level > $indentLevel) {
                    unset($counters[$level]);
                }
            }

            $counters[$indentLevel] = ($counters[$indentLevel] ?? 0) + 1;

            if ($indentLevel === 0) {
                $numbering = $this->toRoman($counters[0]);
            } else {
                $numbering = (string) $counters[$indentLevel];
            }

            return [
                'content' => $row['content'],
                'indentLevel' => $indentLevel,
                'numbering' => $numbering,
                'styleRole' => $indentLevel === 0 ? 'parent' : 'child',
                'fontWeight' => $indentLevel === 0 ? 'bold' : 'regular',
            ];
        }, $rows);
    }

    private function toRoman(int $number): string
    {
        $map = [
            1000 => 'M',
            900 => 'CM',
            500 => 'D',
            400 => 'CD',
            100 => 'C',
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];

        $roman = '';

        foreach ($map as $value => $glyph) {
            while ($number >= $value) {
                $roman .= $glyph;
                $number -= $value;
            }
        }

        return $roman;
    }
}
