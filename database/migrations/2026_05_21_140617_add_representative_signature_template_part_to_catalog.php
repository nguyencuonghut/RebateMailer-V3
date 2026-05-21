<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        DB::table('template_parts')->upsert(
            [[
                'type' => 'representative-signature',
                'code' => 'representative-signature',
                'label' => 'Khối chữ ký đại diện',
                'kind' => 'composite',
                'source_sheet' => null,
                'max_active_versions' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]],
            ['type'],
            ['code', 'label', 'kind', 'source_sheet', 'max_active_versions', 'updated_at'],
        );
    }

    public function down(): void
    {
        DB::table('template_parts')
            ->where('type', 'representative-signature')
            ->delete();
    }
};
