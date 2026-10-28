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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('vendor_name');
            $table->string('vendor_trn')->nullable();
            $table->string('vendor_contact')->nullable();
            $table->date('po_date');
            $table->date('delivery_date')->nullable();
            
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('vat_rate', 5, 4)->default(0.05);
            $table->decimal('vat_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            
            // Status: draft -> approved -> issued -> received -> cancelled
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('branch_id');
            $table->index('status');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->text('description');
            $table->decimal('quantity', 12, 3)->default(1.000);
            $table->string('unit')->default('nos');
            $table->decimal('unit_price', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
