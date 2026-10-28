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
        Schema::create('work_completion_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('certificate_date');
            $table->date('completion_date');
            $table->text('work_description');
            
            $table->decimal('retention_pct', 5, 2)->default(0.00); // e.g. 5.00% or 10.00%
            $table->decimal('retention_amount', 14, 2)->default(0.00);
            
            $table->foreignId('engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_representative_name')->nullable();
            $table->string('client_representative_title')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            
            // Status: draft -> signed -> approved
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_completion_certificates');
    }
};
