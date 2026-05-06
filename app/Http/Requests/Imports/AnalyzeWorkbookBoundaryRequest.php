<?php

namespace App\Http\Requests\Imports;

use App\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class AnalyzeWorkbookBoundaryRequest extends FormRequest
{
    private ?ImportBatch $resolvedImportBatch = null;

    public function authorize(): bool
    {
        return $this->user()?->can('imports.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'importBatchId' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'importBatchId.required' => 'Không tìm thấy batch import để tiếp tục xử lý workbook.',
            'importBatchId.integer' => 'Mã batch import không hợp lệ.',
            'importBatchId.min' => 'Mã batch import không hợp lệ.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $importBatchId = $this->integer('importBatchId');

            if ($importBatchId <= 0) {
                return;
            }

            $importBatch = ImportBatch::query()->find($importBatchId);

            if (! $importBatch instanceof ImportBatch) {
                $validator->errors()->add('importBatchId', 'Không tìm thấy batch import tương ứng.');

                return;
            }

            $this->resolvedImportBatch = $importBatch;

            if (! Storage::disk('local')->exists($importBatch->stored_path)) {
                if ($this->canUsePersistedSheetPreview($importBatch)) {
                    return;
                }

                $validator->errors()->add('importBatchId', 'Không tìm thấy file upload tạm cho batch import này.');
            }
        });
    }

    public function importBatch(): ImportBatch
    {
        if ($this->resolvedImportBatch instanceof ImportBatch) {
            return $this->resolvedImportBatch;
        }

        /** @var ImportBatch $importBatch */
        $importBatch = ImportBatch::query()->findOrFail($this->integer('importBatchId'));
        $this->resolvedImportBatch = $importBatch;

        return $importBatch;
    }

    public function resolvedStoredPath(): string
    {
        return $this->importBatch()->stored_path;
    }

    private function canUsePersistedSheetPreview(ImportBatch $importBatch): bool
    {
        $routeName = $this->route()?->getName();

        if ($routeName === 'imports.preview-aggregated') {
            $aggregatePreview = $importBatch->workbook_summary['aggregatePreview'] ?? null;

            return is_array($aggregatePreview) && isset($aggregatePreview['summary']);
        }

        $sheetName = match ($routeName) {
            'imports.preview-tong-hop' => 'Tổng hợp',
            'imports.preview-khoan-npp' => 'Khoán NPP',
            'imports.preview-cam-ca' => 'Cám cá',
            'imports.preview-key-account' => 'Key Account',
            default => null,
        };

        if ($sheetName === null) {
            return false;
        }

        $sheetPreviews = $importBatch->workbook_summary['sheetPreviews'] ?? null;

        if (! is_array($sheetPreviews) || ! isset($sheetPreviews[$sheetName]) || ! is_array($sheetPreviews[$sheetName])) {
            return false;
        }

        return true;
    }
}
