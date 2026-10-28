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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('zoho_status')->default('pending')->after('zoho_project_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('zoho_status')->default('pending')->after('zoho_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('zoho_status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('zoho_status');
        });
    }
};
