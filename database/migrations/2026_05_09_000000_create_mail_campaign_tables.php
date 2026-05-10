<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('mail_template_canvas_id')->constrained('mail_template_canvases')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->string('dispatch_trigger')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('scheduled_for_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mail_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_campaign_id')->constrained('mail_campaigns')->cascadeOnDelete();
            $table->foreignId('import_batch_aggregated_record_id')->constrained('import_batch_aggregated_records')->cascadeOnDelete();
            $table->string('customer_code');
            $table->string('customer_full_name');
            $table->string('customer_type');
            $table->string('recipient_email')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->text('latest_error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['mail_campaign_id', 'import_batch_aggregated_record_id'], 'mail_campaign_recipient_unique');
            $table->index(['mail_campaign_id', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_campaign_recipients');
        Schema::dropIfExists('mail_campaigns');
    }
};
