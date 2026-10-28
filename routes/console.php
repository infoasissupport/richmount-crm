<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:clear-dummy-data', function () {
    $this->info('Cleaning up dummy transactional records...');

    $tables = [
        'invoice_payments',
        'invoice_items',
        'invoices',
        'work_completion_certificates',
        'purchase_order_items',
        'purchase_orders',
        'project_documents',
        'project_activities',
        'projects',
        'quotation_items',
        'quotation_terms',
        'quotation_versions',
        'quotations',
        'lead_activities',
        'leads',
        'expenses',
        'transfers',
        'zoho_sync_logs',
        'audit_logs',
    ];

    \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    foreach ($tables as $tbl) {
        if (\Illuminate\Support\Facades\Schema::hasTable($tbl)) {
            \Illuminate\Support\Facades\DB::table($tbl)->truncate();
            $this->line("  ✓ Truncated table: {$tbl}");
        }
    }
    \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

    $this->info('Dummy data cleared successfully! Users, roles, permissions, and branches are preserved.');
})->purpose('Remove all dummy leads, quotes, projects, invoices, expenses, and logs');

