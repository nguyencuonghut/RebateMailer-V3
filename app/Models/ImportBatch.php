<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'batch_code',
    'original_file_name',
    'stored_path',
    'uploaded_by',
    'status',
    'workbook_summary',
    'started_at',
    'completed_at',
])]
class ImportBatch extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workbook_summary' => 'json:unicode',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sheetRecords(): HasMany
    {
        return $this->hasMany(ImportBatchSheetRecord::class);
    }

    public function aggregatedRecords(): HasMany
    {
        return $this->hasMany(ImportBatchAggregatedRecord::class);
    }
}
