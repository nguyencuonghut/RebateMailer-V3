<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'mail_template_canvas_id',
    'template_part_id',
    'template_part_version_id',
    'sort_order',
])]
class MailTemplateCanvasPart extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function canvas(): BelongsTo
    {
        return $this->belongsTo(MailTemplateCanvas::class, 'mail_template_canvas_id');
    }

    public function templatePart(): BelongsTo
    {
        return $this->belongsTo(TemplatePart::class);
    }

    public function templatePartVersion(): BelongsTo
    {
        return $this->belongsTo(TemplatePartVersion::class);
    }
}
