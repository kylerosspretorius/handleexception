<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number');
            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');
            $table->string('currency', 3)->default('GBP');

            // From (sender)
            $table->string('from_name');
            $table->string('from_email')->nullable();
            $table->text('from_address')->nullable();
            $table->string('from_phone')->nullable();
            $table->string('from_vat')->nullable();

            // To (client)
            $table->string('to_name');
            $table->string('to_email')->nullable();
            $table->text('to_address')->nullable();
            $table->string('to_phone')->nullable();
            $table->string('to_vat')->nullable();

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('footer')->nullable();
            $table->string('logo_s3_key')->nullable();
            $table->string('pdf_s3_key')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
