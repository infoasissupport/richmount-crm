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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('client_name');
            $table->string('client_trn')->nullable();
            $table->text('client_address')->nullable();
            $table->string('client_po_number')->nullable();
            
            $table->date('invoice_date');
            $table->date('due_date');
            $table->boolean('is_final')->default(false);
            
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('taxable_amount', 14, 2)->default(0.00);
            $table->decimal('vat_rate', 5, 4)->default(0.05); // 5% UAE VAT
            $table->decimal('vat_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('balance_due', 14, 2)->default(0.00);
            
            // Status: draft -> sent -> partially_paid -> paid -> overdue -> void
            $table->string('status')->default('draft');
            $table->string('zoho_invoice_id')->nullable();
            
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('branch_id');
            $table->index('status');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 12, 3)->default(1.000);
            $table->string('unit')->default('nos');
            $table->decimal('unit_price', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_reference')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->default('bank_transfer'); // bank_transfer, cheque, cash
            $table->string('bank_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
