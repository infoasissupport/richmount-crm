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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->string('role_name')->default('engineer')->after('email');
            $table->string('engineer_code')->nullable()->after('role_name');
            $table->string('phone')->nullable()->after('password');
            $table->decimal('cash_balance', 12, 2)->default(0.00)->after('phone');
            $table->string('zoho_petty_cash_account_id')->nullable()->after('cash_balance');
            $table->boolean('all_branches')->default(false)->after('zoho_petty_cash_account_id');
            $table->boolean('is_active')->default(true)->after('all_branches');
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'branch_id',
                'role_name',
                'engineer_code',
                'phone',
                'cash_balance',
                'zoho_petty_cash_account_id',
                'all_branches',
                'is_active',
                'deleted_at'
            ]);
        });
    }
};
