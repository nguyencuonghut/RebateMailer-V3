<?php

namespace App\Services\Imports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreTemporaryImportFileService
{
    /**
     * @return array<string, mixed>
     */
    public function store(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $timestamp = now()->toIso8601String();
        $generatedName = Str::uuid()->toString().'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('imports/tmp', $generatedName.'.'.$extension, 'local');

        return [
            'originalFileName' => $originalName,
            'size' => $file->getSize(),
            'storedPath' => $storedPath,
            'uploadedAt' => $timestamp,
            'nextStep' => 'Sẵn sàng cho bước phân tích tệp Excel.',
        ];
    }
}
