<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_campaign_recipients', function (Blueprint $table): void {
            $table->string('sent_subject_snapshot')->nullable()->after('failed_at');
            $table->longText('sent_html_snapshot')->nullable()->after('sent_subject_snapshot');
            $table->json('sent_signature_snapshot')->nullable()->after('sent_html_snapshot');
            $table->unsignedSmallInteger('snapshot_version')->nullable()->after('sent_signature_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('mail_campaign_recipients', function (Blueprint $table): void {
            $table->dropColumn([
                'sent_subject_snapshot',
                'sent_html_snapshot',
                'sent_signature_snapshot',
                'snapshot_version',
            ]);
        });
    }
};
