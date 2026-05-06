<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batch_aggregated_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('customer_code', 100);
            $table->string('customer_type', 30);
            $table->json('source_sheets');
            $table->json('aggregated_payload');
            $table->json('validation_state')->nullable();
            $table->timestamps();

            $table->unique(['import_batch_id', 'customer_code'], 'import_aggregated_records_batch_customer_unique');
            $table->index(['import_batch_id', 'customer_type'], 'import_aggregated_records_batch_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_aggregated_records');
    }
};
