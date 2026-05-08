<?php

namespace App\Services\Templates;

class BuildRenderedCamCaRowsService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $valueMap
     * @param  array<int, array<string, mixed>>  $programItems
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function build(array $rows, array $valueMap, array $programItems, string $grandTotal, string $totalInWords): array
    {
        $renderedRows = [];
        $errors = [];
        $parentCounter = 0;
        $childCounter = 0;

        foreach ($rows as $row) {
            $rowType = (string) ($row['rowType'] ?? 'blank');
            $columnKey = trim((string) ($row['columnKey'] ?? ''));
            $hideWhenValueZero = (bool) ($row['hideWhenValueZero'] ?? false);
            $fontWeight = $this->resolveFontWeight($row, in_array($rowType, ['parent', 'total'], true));

            if ($rowType === 'value-row') {
                if ($columnKey === '') {
                    continue;
                }

                if (! array_key_exists($columnKey, $valueMap)) {
                    $errors[] = sprintf('Dòng "%s" chưa tìm thấy dữ liệu tương ứng trong sheet Cám cá đã aggregate.', trim((string) ($row['content'] ?? '')));
                    continue;
                }

                $value = (string) ($valueMap[$columnKey] ?? '');

                if ($hideWhenValueZero && $this->isZeroOrBlank($value)) {
                    continue;
                }

                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => '',
                    'content' => trim((string) ($row['content'] ?? '')),
                    'value' => $value,
                    'fontWeight' => $fontWeight,
                    'styleRole' => 'neutral',
                ];

                continue;
            }

            if ($rowType === 'parent') {
                if ($columnKey === '') {
                    continue;
                }

                if (! array_key_exists($columnKey, $valueMap)) {
                    $errors[] = sprintf('Dòng "%s" chưa tìm thấy dữ liệu tương ứng trong sheet Cám cá đã aggregate.', trim((string) ($row['content'] ?? '')));
                    continue;
                }

                $value = (string) ($valueMap[$columnKey] ?? '');

                if ($hideWhenValueZero && $this->isZeroOrBlank($value)) {
                    continue;
                }

                $parentCounter++;
                $childCounter = 0;

                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => $this->toRoman($parentCounter),
                    'content' => trim((string) ($row['content'] ?? '')),
                    'value' => $value,
                    'fontWeight' => $fontWeight,
                    'styleRole' => 'parent',
                ];

                continue;
            }

            if ($rowType === 'child-value') {
                if ($columnKey === '') {
                    continue;
                }

                if (! array_key_exists($columnKey, $valueMap)) {
                    $errors[] = sprintf('Dòng "%s" chưa tìm thấy dữ liệu tương ứng trong sheet Cám cá đã aggregate.', trim((string) ($row['content'] ?? '')));
                    continue;
                }

                $value = (string) ($valueMap[$columnKey] ?? '');

                if ($hideWhenValueZero && $this->isZeroOrBlank($value)) {
                    continue;
                }

                $childCounter++;

                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => (string) $childCounter,
                    'content' => trim((string) ($row['content'] ?? '')),
                    'value' => $value,
                    'fontWeight' => $fontWeight,
                    'styleRole' => 'child',
                ];

                continue;
            }

            if ($rowType === 'child-program-loop') {
                foreach ($programItems as $programItem) {
                    if (! is_array($programItem) || $this->shouldHideProgramItem($programItem)) {
                        continue;
                    }

                    $childCounter++;

                    $renderedRows[] = [
                        'rowType' => $rowType,
                        'numbering' => (string) $childCounter,
                        'content' => trim((string) ($programItem['content'] ?? '')),
                        'value' => trim((string) ($programItem['amount'] ?? '')),
                        'fontWeight' => $fontWeight,
                        'styleRole' => 'child',
                    ];
                }

                continue;
            }

            if ($rowType === 'blank') {
                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => '',
                    'content' => '',
                    'value' => '',
                    'fontWeight' => $fontWeight,
                    'styleRole' => 'neutral',
                ];

                continue;
            }

            if ($rowType === 'total') {
                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => '',
                    'content' => trim((string) ($row['content'] ?? '')) !== '' ? trim((string) $row['content']) : 'Cộng',
                    'value' => $grandTotal,
                    'fontWeight' => $fontWeight,
                    'styleRole' => 'neutral',
                ];

                continue;
            }

            if ($rowType === 'in-words') {
                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => '',
                    'content' => trim((string) ($row['content'] ?? '')) !== '' ? trim((string) $row['content']) : 'Bằng chữ:',
                    'value' => $totalInWords,
                    'fontWeight' => $fontWeight,
                    'styleRole' => 'neutral',
                ];
            }
        }

        return [
            'rows' => $renderedRows,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $programItem
     */
    private function shouldHideProgramItem(array $programItem): bool
    {
        return $this->isZeroOrBlank($programItem['content'] ?? null)
            && $this->isZeroOrBlank($programItem['amount'] ?? null);
    }

    private function isZeroOrBlank(mixed $value): bool
    {
        $normalized = str_replace(',', '', trim((string) $value));

        if ($normalized === '') {
            return true;
        }

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            return false;
        }

        return (float) $normalized === 0.0;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveFontWeight(array $row, bool $defaultBold = false): string
    {
        $isBold = array_key_exists('isBold', $row)
            ? (bool) $row['isBold']
            : $defaultBold;

        return $isBold ? 'bold' : 'regular';
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
