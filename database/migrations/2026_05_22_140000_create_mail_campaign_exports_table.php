<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_campaign_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('export_type', 32)->default('pdf');
            $table->string('status', 32)->default('queued');
            $table->foreignId('requested_by')->constrained('users');
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('exported_recipients')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['mail_campaign_id', 'export_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_campaign_exports');
    }
};
