<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Line item kind: 'item' (priced row) or 'section' (a heading such as PRELIMINARIES that groups the
     * rows below it; no quantity or price). Existing rows are items.
     */
    public function up(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->string('type', 20)->default('item')->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
