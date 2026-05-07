<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'template_part_id',
    'version_no',
    'version_label',
    'text_template',
    'structure_json',
    'is_active',
    'activated_at',
    'legacy_mail_template_id',
    'created_by',
    'updated_by',
])]
class TemplatePartVersion extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'structure_json' => 'json:unicode',
            'is_active' => 'boolean',
            'version_no' => 'integer',
            'activated_at' => 'datetime',
        ];
    }

    public function templatePart(): BelongsTo
    {
        return $this->belongsTo(TemplatePart::class);
    }

    public function legacyMailTemplate(): BelongsTo
    {
        return $this->belongsTo(MailTemplate::class, 'legacy_mail_template_id');
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
