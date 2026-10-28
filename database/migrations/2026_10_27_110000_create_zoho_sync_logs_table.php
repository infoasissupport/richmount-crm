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
        Schema::create('zoho_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // project, invoice, expense, payment, customer
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('reference'); // PRJ-2026-0001, INV-2026-0001, EXP-2026-0001
            $table->string('action'); // create_project, create_invoice, record_expense, record_payment
            $table->string('status')->default('queued'); // queued, syncing, synced, failed
            $table->string('zoho_id')->nullable(); // Zoho Books entity ID
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoho_sync_logs');
    }
};
