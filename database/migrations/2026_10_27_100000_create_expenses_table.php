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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('category')->default('materials'); // materials, transport, labor, meals, tools, fuel, permits, miscellaneous
            $table->string('vendor_name')->nullable();
            $table->string('vendor_trn')->nullable();
            $table->string('bill_number')->nullable();
            $table->date('date');
            
            $table->string('type')->default('cash'); // cash, card
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('vat_rate', 5, 4)->default(0.05);
            $table->decimal('vat_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            
            $table->string('receipt_file')->nullable();
            $table->json('ocr_data')->nullable();
            $table->text('description')->nullable();
            
            // Status: pending -> approved -> rejected
            $table->string('status')->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Zoho Books integration fields
            $table->string('zoho_expense_id')->nullable();
            $table->string('zoho_status')->default('pending'); // pending, syncing, synced, failed
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('branch_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
