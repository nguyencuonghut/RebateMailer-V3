<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batch_sheet_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('sheet_name', 50);
            $table->string('customer_code', 100);
            $table->unsignedInteger('row_number');
            $table->string('customer_type_inferred', 30);
            $table->json('parsed_payload');
            $table->timestamps();

            $table->unique(['import_batch_id', 'sheet_name', 'row_number'], 'import_sheet_records_batch_sheet_row_unique');
            $table->index(['import_batch_id', 'sheet_name'], 'import_sheet_records_batch_sheet_index');
            $table->index(['import_batch_id', 'customer_code'], 'import_sheet_records_batch_customer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_sheet_records');
    }
};
