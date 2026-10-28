<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'id' => 1,
                'name' => 'Dubai Branch',
                'short_name' => 'Dubai',
                'code' => 'Branch 1',
                'country' => 'UAE',
                'quote_format' => 'format_1',
                'trn_number' => '100 234 567 800 001',
                'address' => 'Business Bay, Tower A, Suite 1402, Dubai, UAE',
                'phone' => '+971 4 234 5678',
                'email' => 'dubai@richmount.ae',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Abu Dhabi Branch',
                'short_name' => 'Abu Dhabi',
                'code' => 'Branch 2',
                'country' => 'UAE',
                'quote_format' => 'format_2',
                'trn_number' => '100 234 567 800 002',
                'address' => 'Al Reem Island, Sky Tower, Floor 18, Abu Dhabi, UAE',
                'phone' => '+971 2 345 6789',
                'email' => 'abudhabi@richmount.ae',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Sharjah Branch',
                'short_name' => 'Sharjah',
                'code' => 'Branch 3',
                'country' => 'UAE',
                'quote_format' => 'format_3',
                'trn_number' => '100 234 567 800 003',
                'address' => 'Industrial Area 12, Rich Mount Complex, Sharjah, UAE',
                'phone' => '+971 6 456 7890',
                'email' => 'sharjah@richmount.ae',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['id' => $branch['id']], $branch);
        }
    }
}
