<?php

namespace App\Services\Templates;

class BuildRenderedTongHopRowsService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $valueMap
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function build(array $rows, array $valueMap): array
    {
        $errors = [];

        $renderedRows = array_values(array_filter(array_map(function (array $row) use ($valueMap, &$errors): ?array {
            $content = trim((string) ($row['content'] ?? ''));
            $columnKey = trim((string) ($row['columnKey'] ?? $content));
            $value = $valueMap[$columnKey] ?? '';

            if ($columnKey !== '' && ! array_key_exists($columnKey, $valueMap)) {
                $errors[] = sprintf(
                    'Dòng "%s" chưa tìm thấy dữ liệu tương ứng trong sheet Tổng hợp đã aggregate.',
                    $content,
                );
            }

            if (! $this->shouldRenderRow($row, $value)) {
                return null;
            }

            return [
                ...$row,
                'columnKey' => $columnKey,
                'value' => $value,
            ];
        }, $rows), static fn (?array $row): bool => $row !== null));

        $renderedRows = $this->recalculateVisibleNumbering($renderedRows);

        return [
            'rows' => $renderedRows,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function shouldRenderRow(array $row, string $value): bool
    {
        if (! (bool) ($row['hideWhenValueZero'] ?? false)) {
            return true;
        }

        return ! $this->shouldHideWhenValueZero($value);
    }

    public function shouldHideWhenValueZero(string $value): bool
    {
        $normalized = str_replace(',', '', trim($value));

        if ($normalized === '') {
            return true;
        }

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $normalized)) {
            return false;
        }

        return (float) $normalized === 0.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function recalculateVisibleNumbering(array $rows): array
    {
        $parentCounter = 0;
        $childCounter = 0;

        return array_map(function (array $row) use (&$parentCounter, &$childCounter): array {
            $rowType = (string) ($row['rowType'] ?? '');

            if ($rowType === 'parent') {
                $parentCounter++;
                $childCounter = 0;
                $row['numbering'] = $this->toRoman($parentCounter);
            } elseif ($rowType === 'child') {
                $childCounter++;
                $row['numbering'] = (string) $childCounter;
            } else {
                $row['numbering'] = '';
            }

            return $row;
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
