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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_number')->unique();
            $table->string('name');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('client_name');
            $table->string('client_po_number')->nullable();
            $table->date('client_po_date')->nullable();
            $table->decimal('client_po_amount', 14, 2)->default(0.00);
            $table->string('client_po_file')->nullable();
            
            $table->decimal('contract_value', 14, 2)->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // Lifecycle: popending -> open -> inprogress -> onhold -> completed -> invoicepending -> closed
            $table->string('status')->default('popending');
            $table->unsignedTinyInteger('progress_pct')->default(0);
            
            $table->text('description')->nullable();
            $table->text('completion_report')->nullable();
            $table->string('hold_reason')->nullable();
            $table->string('zoho_project_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('engineer_id');
            $table->index('status');
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_type')->nullable();
            $table->string('kind')->default('general'); // po, wcc, contract, drawing, permit, general
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind')->default('status');
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
        Schema::dropIfExists('project_activities');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('projects');
    }
};
