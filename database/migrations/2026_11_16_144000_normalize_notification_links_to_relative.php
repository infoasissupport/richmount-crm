<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')->orderBy('id')->each(function ($notification) {
            $data = json_decode($notification->data, true);
            if (! is_array($data) || ! isset($data['link']) || ! is_string($data['link'])) {
                return;
            }

            $sanitized = NotificationController::sanitizeNotificationLink($data['link']);
            if ($sanitized !== '#' && $sanitized !== $data['link']) {
                $data['link'] = $sanitized;
                DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update(['data' => json_encode($data)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: relative links are always preferred.
    }
};
