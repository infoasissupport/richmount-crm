<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Up to 3 photos per quotation line item (paths on the public disk, quotation_photos/...).
     */
    public function up(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_line_items', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
