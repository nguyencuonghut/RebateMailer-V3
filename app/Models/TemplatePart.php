<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'type',
    'code',
    'label',
    'kind',
    'source_sheet',
    'max_active_versions',
])]
class TemplatePart extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_active_versions' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TemplatePartVersion::class);
    }

    public function canvasBindings(): HasMany
    {
        return $this->hasMany(MailTemplateCanvasPart::class);
    }
}
