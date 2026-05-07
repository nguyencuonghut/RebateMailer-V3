<?php

namespace App\Services\Templates;

class BuildRenderedKhoanNppRowsService
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $khoanNpp
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function build(array $rows, array $khoanNpp): array
    {
        $renderedRows = [];
        $errors = [];
        $programNumber = 0;

        foreach ($rows as $row) {
            $rowType = (string) ($row['rowType'] ?? 'blank');

            if ($rowType === 'program-loop') {
                foreach (($khoanNpp['programItems'] ?? []) as $programItem) {
                    if (! is_array($programItem) || $this->shouldHideProgramItem($programItem)) {
                        continue;
                    }

                    $programNumber++;

                    $renderedRows[] = [
                        'rowType' => $rowType,
                        'numbering' => (string) $programNumber,
                        'content' => trim((string) ($programItem['content'] ?? '')),
                        'quantity' => trim((string) ($programItem['quantity'] ?? '')),
                        'supportRate' => trim((string) ($programItem['supportRate'] ?? '')),
                        'amount' => trim((string) ($programItem['amount'] ?? '')),
                        'fontWeight' => $this->resolveFontWeight($row),
                        'styleRole' => 'neutral',
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
                    'fontWeight' => $this->resolveFontWeight($row),
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
                    'amount' => trim((string) ($khoanNpp['grandTotal'] ?? '')),
                    'fontWeight' => $this->resolveFontWeight($row, true),
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
                    'amount' => trim((string) ($khoanNpp['totalInWords'] ?? '')),
                    'fontWeight' => $this->resolveFontWeight($row),
                    'styleRole' => 'neutral',
                ];

                continue;
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
            && $this->isZeroOrBlank($programItem['quantity'] ?? null)
            && $this->isZeroOrBlank($programItem['supportRate'] ?? null)
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
}
