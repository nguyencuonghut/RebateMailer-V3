<?php

namespace App\Services\Templates;

class GenerateTemplateRowNumberingService
{
    /**
     * @param  array<int, array{
     *   content: string,
     *   indentLevel?: int,
     *   rowType?: string,
     *   columnKey?: ?string,
     *   hideWhenValueZero?: bool,
     *   isBold?: bool
     * }>  $rows
     * @return array<int, array{
     *   content: string,
     *   indentLevel: int,
     *   rowType?: string,
     *   columnKey?: ?string,
     *   hideWhenValueZero?: bool,
     *   isBold?: bool,
     *   numbering: string,
     *   styleRole: string,
     *   fontWeight: string
     * }>
     */
    public function generate(array $rows): array
    {
        if ($this->usesSemanticRowTypes($rows)) {
            return $this->generateForSemanticRows($rows);
        }

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

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function usesSemanticRowTypes(array $rows): bool
    {
        foreach ($rows as $row) {
            if (array_key_exists('rowType', $row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function generateForSemanticRows(array $rows): array
    {
        $parentCounter = 0;
        $childCounter = 0;

        return array_map(function (array $row) use (&$parentCounter, &$childCounter): array {
            $rowType = (string) ($row['rowType'] ?? 'blank');
            $indentLevel = $rowType === 'child' ? 1 : 0;
            $numbering = '';
            $styleRole = 'neutral';

            if ($rowType === 'parent') {
                $parentCounter++;
                $childCounter = 0;
                $numbering = $this->toRoman($parentCounter);
                $styleRole = 'parent';
            } elseif ($rowType === 'child') {
                $childCounter++;
                $numbering = (string) $childCounter;
                $styleRole = 'child';
            } elseif (in_array($rowType, ['total', 'text', 'blank', 'data'], true)) {
                $styleRole = 'neutral';
            }

            $isBold = array_key_exists('isBold', $row)
                ? (bool) $row['isBold']
                : in_array($rowType, ['parent', 'total'], true);

            return [
                'content' => (string) ($row['content'] ?? ''),
                'indentLevel' => $indentLevel,
                'rowType' => $rowType,
                'columnKey' => $row['columnKey'] ?? null,
                'hideWhenValueZero' => (bool) ($row['hideWhenValueZero'] ?? false),
                'isBold' => $isBold,
                'numbering' => $numbering,
                'styleRole' => $styleRole,
                'fontWeight' => $isBold ? 'bold' : 'regular',
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
