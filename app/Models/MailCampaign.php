<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'import_batch_id',
    'mail_template_canvas_id',
    'notes',
    'status',
    'dispatch_trigger',
    'scheduled_at',
    'scheduled_for_at',
    'created_by',
    'updated_by',
])]
class MailCampaign extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'scheduled_for_at' => 'datetime',
        ];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function templateCanvas(): BelongsTo
    {
        return $this->belongsTo(MailTemplateCanvas::class, 'mail_template_canvas_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MailCampaignRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
