<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed dummy contracting records for testing/demo environments.
     */
    public function run(): void
    {
        $this->call([
            LeadSeeder::class,
            QuotationSeeder::class,
            ProjectSeeder::class,
            InvoiceSeeder::class,
            TransferSeeder::class,
            ExpenseSeeder::class,
            ZohoSyncLogSeeder::class,
        ]);
    }
}
