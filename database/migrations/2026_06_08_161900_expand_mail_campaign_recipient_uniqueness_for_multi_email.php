<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_campaign_recipients', function (Blueprint $table): void {
            $table->dropUnique('mail_campaign_recipient_unique');
            $table->unique(
                ['mail_campaign_id', 'import_batch_aggregated_record_id', 'recipient_email'],
                'mail_campaign_recipient_email_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('mail_campaign_recipients', function (Blueprint $table): void {
            $table->dropUnique('mail_campaign_recipient_email_unique');
            $table->unique(
                ['mail_campaign_id', 'import_batch_aggregated_record_id'],
                'mail_campaign_recipient_unique',
            );
        });
    }
};
