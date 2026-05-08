<?php

namespace App\Services\Templates;

class BuildRenderedKeyAccountRowsService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array{quantity: string, supportRate: string, amount: string, defaultValueColumn: string}>  $valueMap
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

            if (in_array($rowType, ['value-row', 'parent', 'child-value'], true)) {
                if ($columnKey === '') {
                    continue;
                }

                if (! array_key_exists($columnKey, $valueMap)) {
                    $errors[] = sprintf('Dòng "%s" chưa tìm thấy dữ liệu tương ứng trong sheet Key Account đã aggregate.', trim((string) ($row['content'] ?? '')));
                    continue;
                }

                $valueColumn = $this->resolveValueColumn($row, $valueMap[$columnKey]);
                $slots = $this->makeValueSlots($valueColumn, (string) ($valueMap[$columnKey][$valueColumn] ?? ''));

                if ($hideWhenValueZero && $this->shouldHideValueSlots($slots)) {
                    continue;
                }

                if ($rowType === 'parent') {
                    $parentCounter++;
                    $childCounter = 0;
                } elseif ($rowType === 'child-value') {
                    $childCounter++;
                }

                $renderedRows[] = [
                    'rowType' => $rowType,
                    'numbering' => $rowType === 'parent'
                        ? $this->toRoman($parentCounter)
                        : ($rowType === 'child-value' ? (string) $childCounter : ''),
                    'content' => trim((string) ($row['content'] ?? '')),
                    'quantity' => $slots['quantity'],
                    'supportRate' => $slots['supportRate'],
                    'amount' => $slots['amount'],
                    'fontWeight' => $fontWeight,
                    'styleRole' => $rowType === 'parent' ? 'parent' : ($rowType === 'child-value' ? 'child' : 'neutral'),
                ];

                continue;
            }

            if ($rowType === 'child-program-loop') {
                foreach ($programItems as $programItem) {
                    if (! is_array($programItem) || $this->shouldSkipProgramItem($programItem)) {
                        continue;
                    }

                    $childCounter++;

                    $renderedRows[] = [
                        'rowType' => $rowType,
                        'numbering' => (string) $childCounter,
                        'content' => trim((string) ($programItem['content'] ?? '')),
                        'quantity' => trim((string) ($programItem['quantity'] ?? '')),
                        'supportRate' => trim((string) ($programItem['supportRate'] ?? '')),
                        'amount' => trim((string) ($programItem['amount'] ?? '')),
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
                    'quantity' => '',
                    'supportRate' => '',
                    'amount' => '',
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
                    'quantity' => '',
                    'supportRate' => '',
                    'amount' => $grandTotal,
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
                    'quantity' => '',
                    'supportRate' => '',
                    'amount' => $totalInWords,
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
     * @param  array<string, mixed>  $row
     * @param  array{quantity: string, supportRate: string, amount: string, defaultValueColumn: string}  $valueEntry
     */
    private function resolveValueColumn(array $row, array $valueEntry): string
    {
        $valueColumn = (string) ($row['valueColumn'] ?? '');
        $defaultValueColumn = (string) ($valueEntry['defaultValueColumn'] ?? 'amount');

        if (in_array($valueColumn, ['quantity', 'supportRate', 'amount'], true)) {
            $selectedValue = trim((string) ($valueEntry[$valueColumn] ?? ''));
            $defaultValue = trim((string) ($valueEntry[$defaultValueColumn] ?? ''));

            if ($selectedValue === '' && $defaultValue !== '') {
                return $defaultValueColumn;
            }

            return $valueColumn;
        }

        return $defaultValueColumn;
    }

    /**
     * @return array{quantity: string, supportRate: string, amount: string}
     */
    private function makeValueSlots(string $valueColumn, string $value): array
    {
        return [
            'quantity' => $valueColumn === 'quantity' ? $value : '',
            'supportRate' => $valueColumn === 'supportRate' ? $value : '',
            'amount' => $valueColumn === 'amount' ? $value : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $programItem
     */
    private function shouldSkipProgramItem(array $programItem): bool
    {
        return $this->isBlank($programItem['content'] ?? null)
            && $this->isBlank($programItem['quantity'] ?? null)
            && $this->isBlank($programItem['supportRate'] ?? null)
            && $this->isBlank($programItem['amount'] ?? null);
    }

    /**
     * @param  array{quantity: string, supportRate: string, amount: string}  $slots
     */
    private function shouldHideValueSlots(array $slots): bool
    {
        return $this->isZeroOrBlank($slots['quantity'])
            && $this->isZeroOrBlank($slots['supportRate'])
            && $this->isZeroOrBlank($slots['amount']);
    }

    private function isBlank(mixed $value): bool
    {
        return trim((string) $value) === '';
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
