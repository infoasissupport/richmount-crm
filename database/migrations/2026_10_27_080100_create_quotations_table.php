<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('prepared_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('quote_date');
            $table->integer('validity_days')->default(30);
            $table->string('output_type')->default('tax'); // tax, notax, tech
            $table->string('status')->default('draft'); // draft, pending, approved, rejected, sent, awarded
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->string('discount_type')->default('amt'); // amt, pct
            $table->decimal('discount_value', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('taxable_amount', 14, 2)->default(0.00);
            $table->decimal('vat_rate', 5, 4)->default(0.05); // 0.05 or 0.00
            $table->decimal('vat_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            $table->text('technical_description')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('auto_saved_at')->nullable();
            $table->date('award_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('lead_id');
            $table->index('branch_id');
            $table->index('prepared_by_id');
            $table->index('status');
        });

        Schema::create('quotation_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 12, 3)->default(1.000);
            $table->string('unit')->default('nos'); // lump sum, m², m³, m, ton, nos, set, day
            $table->decimal('unit_price', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // created, edited, autosaved, submitted, approved, rejected, sent, awarded, cloned
            $table->string('text');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_histories');
        Schema::dropIfExists('quotation_attachments');
        Schema::dropIfExists('quotation_line_items');
        Schema::dropIfExists('quotations');
    }
};
