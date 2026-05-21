<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_campaign_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_campaign_id')->constrained('mail_campaigns')->cascadeOnDelete();
            $table->string('export_type', 50);
            $table->string('status', 30);
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('exported_recipients')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['mail_campaign_id', 'export_type']);
            $table->index(['mail_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_campaign_exports');
    }
};
