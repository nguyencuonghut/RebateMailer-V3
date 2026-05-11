<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_campaign_recipients', function (Blueprint $table) {
            $table->index('delivery_status', 'mcr_delivery_status_idx');
            $table->index('recipient_email', 'mcr_recipient_email_idx');
        });

        Schema::table('mail_campaign_recipient_attempts', function (Blueprint $table) {
            $table->index('status', 'mcra_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('mail_campaign_recipients', function (Blueprint $table) {
            $table->dropIndex('mcr_delivery_status_idx');
            $table->dropIndex('mcr_recipient_email_idx');
        });

        Schema::table('mail_campaign_recipient_attempts', function (Blueprint $table) {
            $table->dropIndex('mcra_status_idx');
        });
    }
};
