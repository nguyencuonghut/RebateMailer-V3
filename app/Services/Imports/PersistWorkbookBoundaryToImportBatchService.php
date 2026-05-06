<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class PersistWorkbookBoundaryToImportBatchService
{
    /**
     * @param  array<string, mixed>  $workbookBoundary
     */
    public function persist(ImportBatch $importBatch, array $workbookBoundary): ImportBatch
    {
        $importBatch->forceFill([
            'status' => 'workbook_analyzed',
            'workbook_summary' => [
                'contract' => $workbookBoundary['contract'] ?? [],
                'summary' => $workbookBoundary['summary'] ?? [],
                'expectedSheets' => $workbookBoundary['expectedSheets'] ?? [],
                'detectedSheets' => $workbookBoundary['detectedSheets'] ?? [],
                'missingSheets' => $workbookBoundary['missingSheets'] ?? [],
                'unexpectedSheets' => $workbookBoundary['unexpectedSheets'] ?? [],
                'sheets' => $workbookBoundary['sheets'] ?? [],
                'nextStep' => $workbookBoundary['nextStep'] ?? null,
            ],
        ])->save();

        return $importBatch->refresh();
    }
}
