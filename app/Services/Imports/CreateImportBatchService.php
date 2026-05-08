<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;

class CreateImportBatchService
{
    public function create(
        string $name,
        string $originalFileName,
        string $storedPath,
        ?int $uploadedBy,
    ): ImportBatch {
        return ImportBatch::query()->create([
            'batch_code' => 'IMP-'.now()->format('YmdHis').'-'.strtoupper(str()->random(6)),
            'name' => $name,
            'original_file_name' => $originalFileName,
            'stored_path' => $storedPath,
            'uploaded_by' => $uploadedBy,
            'status' => 'uploaded',
            'started_at' => now(),
        ]);
    }
}
