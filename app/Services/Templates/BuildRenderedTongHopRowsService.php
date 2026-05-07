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
}
