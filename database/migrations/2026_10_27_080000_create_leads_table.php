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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number')->unique();
            $table->date('lead_date')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('company_name');
            $table->string('rfq_number')->nullable();
            $table->string('subject');
            $table->string('department')->default('construction'); // construction, interior, renovation, mep
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('assigned_engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('Website'); // Website, Referral, Exhibition, Email, Phone, Social Media
            $table->decimal('estimated_value', 12, 2)->default(0.00);
            $table->string('status')->default('New'); // New, Contacted, Proposal, Negotiation, Won, Lost, Closed
            $table->string('stage')->default('new'); // new, draft, sent, awarded
            $table->dateTime('stage_date')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('assigned_engineer_id');
            $table->index('stage');
            $table->index('status');
        });

        Schema::create('lead_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_type')->nullable();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind')->default('status'); // status, note, assignment, quote
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
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('lead_attachments');
        Schema::dropIfExists('leads');
    }
};
