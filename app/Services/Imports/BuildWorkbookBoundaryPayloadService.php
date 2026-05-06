<?php

namespace App\Services\Imports;

class BuildWorkbookBoundaryPayloadService
{
    /**
     * @param  array<string, mixed>  $analysis
     * @return array<string, mixed>
     */
    public function build(array $analysis): array
    {
        $expectedSheets = array_values($analysis['expectedSheets'] ?? []);
        $detectedSheets = array_values($analysis['detectedSheets'] ?? []);
        $missingSheets = array_values($analysis['missingSheets'] ?? []);
        $unexpectedSheets = array_values($analysis['unexpectedSheets'] ?? []);
        $headerRowBySheet = $analysis['headerRowBySheet'] ?? [];
        $dataRowCountBySheet = $analysis['dataRowCountBySheet'] ?? [];
        $emptyStateBySheet = $analysis['emptyStateBySheet'] ?? [];

        $sheets = array_map(
            static function (string $sheetName) use (
                $detectedSheets,
                $missingSheets,
                $headerRowBySheet,
                $dataRowCountBySheet,
                $emptyStateBySheet,
            ): array {
                return [
                    'name' => $sheetName,
                    'present' => in_array($sheetName, $detectedSheets, true),
                    'missing' => in_array($sheetName, $missingSheets, true),
                    'headerRow' => array_values($headerRowBySheet[$sheetName] ?? []),
                    'dataRowCount' => (int) ($dataRowCountBySheet[$sheetName] ?? 0),
                    'isEmpty' => (bool) ($emptyStateBySheet[$sheetName] ?? true),
                ];
            },
            $expectedSheets,
        );

        return [
            'storedPath' => $analysis['storedPath'],
            'contract' => [
                'version' => '1.2-H',
                'stage' => 'workbook-boundary',
                'expectedSheetCount' => count($expectedSheets),
            ],
            'summary' => [
                'detectedSheetCount' => (int) ($analysis['sheetCount'] ?? count($detectedSheets)),
                'missingSheetCount' => count($missingSheets),
                'unexpectedSheetCount' => count($unexpectedSheets),
            ],
            'expectedSheets' => $expectedSheets,
            'detectedSheets' => $detectedSheets,
            'missingSheets' => $missingSheets,
            'unexpectedSheets' => $unexpectedSheets,
            'sheets' => $sheets,
            'nextStep' => $analysis['nextStep'],
        ];
    }
}
