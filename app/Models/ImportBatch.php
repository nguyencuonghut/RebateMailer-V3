<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'batch_code',
    'name',
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

    public function campaigns(): HasMany
    {
        return $this->hasMany(MailCampaign::class);
    }

    public function deleteWithFile(): void
    {
        if ($this->stored_path && Storage::disk('local')->exists($this->stored_path)) {
            Storage::disk('local')->delete($this->stored_path);
        }

        $this->delete();
    }
}
