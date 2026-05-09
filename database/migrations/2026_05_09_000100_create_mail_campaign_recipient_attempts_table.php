<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_campaign_recipient_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_campaign_recipient_id')->constrained('mail_campaign_recipients')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('status');
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['mail_campaign_recipient_id', 'created_at'], 'mail_campaign_recipient_attempts_recipient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_campaign_recipient_attempts');
    }
};
