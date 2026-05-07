<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_parts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('kind');
            $table->string('source_sheet')->nullable();
            $table->unsignedSmallInteger('max_active_versions');
            $table->timestamps();
        });

        Schema::create('template_part_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_part_id')->constrained('template_parts')->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('version_label');
            $table->text('text_template')->nullable();
            $table->json('structure_json')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('legacy_mail_template_id')->nullable()->constrained('mail_templates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['template_part_id', 'version_no']);
            $table->index(['template_part_id', 'legacy_mail_template_id']);
        });

        Schema::create('mail_template_canvases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->foreignId('legacy_mail_template_id')->nullable()->unique()->constrained('mail_templates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mail_template_canvas_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_template_canvas_id')->constrained('mail_template_canvases')->cascadeOnDelete();
            $table->foreignId('template_part_id')->constrained('template_parts')->cascadeOnDelete();
            $table->foreignId('template_part_version_id')->constrained('template_part_versions')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['mail_template_canvas_id', 'template_part_id']);
        });

        $this->seedTemplatePartCatalog();
        $this->backfillLegacyMailTemplates();
        $this->enforceActivePolicy();
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_template_canvas_parts');
        Schema::dropIfExists('mail_template_canvases');
        Schema::dropIfExists('template_part_versions');
        Schema::dropIfExists('template_parts');
    }

    private function seedTemplatePartCatalog(): void
    {
        $timestamp = now();

        DB::table('template_parts')->upsert(
            [
                ['type' => 'subject', 'code' => 'subject', 'label' => 'Subject', 'kind' => 'text', 'source_sheet' => null, 'max_active_versions' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['type' => 'greeting', 'code' => 'greeting', 'label' => 'Lời chào', 'kind' => 'text', 'source_sheet' => null, 'max_active_versions' => 2, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['type' => 'tong-hop-table', 'code' => 'tong-hop', 'label' => 'Table Chế độ tháng', 'kind' => 'table', 'source_sheet' => 'Tổng hợp', 'max_active_versions' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['type' => 'khoan-npp-table', 'code' => 'khoan-npp', 'label' => 'Table Chương trình khoán đặc biệt', 'kind' => 'table', 'source_sheet' => 'Khoán NPP', 'max_active_versions' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['type' => 'cam-ca-table', 'code' => 'cam-ca', 'label' => 'Table Chiết khấu cám cá', 'kind' => 'table', 'source_sheet' => 'Cám cá', 'max_active_versions' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
                ['type' => 'key-account-table', 'code' => 'key-account', 'label' => 'Table Chiết khấu Key Account', 'kind' => 'table', 'source_sheet' => 'Key Account', 'max_active_versions' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ],
            ['type'],
            ['code', 'label', 'kind', 'source_sheet', 'max_active_versions', 'updated_at'],
        );
    }

    private function backfillLegacyMailTemplates(): void
    {
        $partsByType = DB::table('template_parts')->get()->keyBy('type');
        $versionCounters = DB::table('template_part_versions')
            ->select('template_part_id', DB::raw('MAX(version_no) AS max_version_no'))
            ->groupBy('template_part_id')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                (int) $row->template_part_id => (int) $row->max_version_no,
            ]);

        $legacyTemplates = DB::table('mail_templates')->orderBy('id')->get();

        foreach ($legacyTemplates as $legacyTemplate) {
            $canvasId = DB::table('mail_template_canvases')->insertGetId([
                'name' => $legacyTemplate->name,
                'is_active' => (bool) $legacyTemplate->is_active,
                'legacy_mail_template_id' => $legacyTemplate->id,
                'created_by' => $legacyTemplate->created_by,
                'updated_by' => $legacyTemplate->updated_by,
                'created_at' => $legacyTemplate->created_at,
                'updated_at' => $legacyTemplate->updated_at,
            ]);

            $sections = collect($this->decodeJson($legacyTemplate->structure_json)['sections'] ?? [])
                ->filter(static fn (mixed $section): bool => is_array($section))
                ->mapWithKeys(static fn (array $section): array => [
                    (string) ($section['type'] ?? '') => $section,
                ]);

            $legacyPayloads = [
                'subject' => $this->normalizeTextPayload($sections->get('subject')['content'] ?? $legacyTemplate->subject_template),
                'greeting' => $this->normalizeTextPayload($sections->get('greeting')['content'] ?? null),
                'tong-hop-table' => $this->normalizeStructurePayload($sections->get('tong-hop-table')),
                'khoan-npp-table' => $this->normalizeStructurePayload($sections->get('khoan-npp-table')),
                'cam-ca-table' => $this->normalizeStructurePayload($sections->get('cam-ca-table')),
                'key-account-table' => $this->normalizeStructurePayload($sections->get('key-account-table')),
            ];

            foreach (array_values(array_filter(array_keys($legacyPayloads), static fn (string $type): bool => $legacyPayloads[$type] !== null)) as $sortOrder => $type) {
                $templatePart = $partsByType->get($type);

                if (! $templatePart) {
                    continue;
                }

                $versionNo = ((int) $versionCounters->get($templatePart->id, 0)) + 1;
                $versionCounters->put($templatePart->id, $versionNo);
                $payload = $legacyPayloads[$type];

                $templatePartVersionId = DB::table('template_part_versions')->insertGetId([
                    'template_part_id' => $templatePart->id,
                    'version_no' => $versionNo,
                    'version_label' => sprintf('Legacy %s', $legacyTemplate->name),
                    'text_template' => is_string($payload) ? $payload : null,
                    'structure_json' => is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    'is_active' => (bool) $legacyTemplate->is_active,
                    'activated_at' => $legacyTemplate->is_active ? ($legacyTemplate->updated_at ?? $legacyTemplate->created_at ?? now()) : null,
                    'legacy_mail_template_id' => $legacyTemplate->id,
                    'created_by' => $legacyTemplate->created_by,
                    'updated_by' => $legacyTemplate->updated_by,
                    'created_at' => $legacyTemplate->created_at,
                    'updated_at' => $legacyTemplate->updated_at,
                ]);

                DB::table('mail_template_canvas_parts')->insert([
                    'mail_template_canvas_id' => $canvasId,
                    'template_part_id' => $templatePart->id,
                    'template_part_version_id' => $templatePartVersionId,
                    'sort_order' => $sortOrder,
                    'created_at' => $legacyTemplate->created_at,
                    'updated_at' => $legacyTemplate->updated_at,
                ]);
            }
        }
    }

    private function enforceActivePolicy(): void
    {
        $templateParts = DB::table('template_parts')->get();

        foreach ($templateParts as $templatePart) {
            $activeVersionIdsToKeep = DB::table('template_part_versions')
                ->where('template_part_id', $templatePart->id)
                ->where('is_active', true)
                ->orderByDesc('activated_at')
                ->orderByDesc('id')
                ->limit($templatePart->max_active_versions)
                ->pluck('id');

            DB::table('template_part_versions')
                ->where('template_part_id', $templatePart->id)
                ->where('is_active', true)
                ->whereNotIn('id', $activeVersionIdsToKeep->all())
                ->update([
                    'is_active' => false,
                ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeTextPayload(mixed $value): ?string
    {
        $content = trim((string) $value);

        return $content === '' ? null : $content;
    }

    /**
     * @param  mixed  $value
     * @return array<string, mixed>|null
     */
    private function normalizeStructurePayload(mixed $value): ?array
    {
        return is_array($value) ? $value : null;
    }
};
