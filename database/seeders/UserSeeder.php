<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('demo1234');

        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'super_admin',
                'branch_id' => 1,
                'all_branches' => true,
                'phone' => '+971 50 111 2233',
                'cash_balance' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'Aisha Rahman',
                'email' => 'aisha.rahman@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'estimation',
                'branch_id' => 1,
                'all_branches' => true,
                'phone' => '+971 50 222 3344',
                'cash_balance' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'Ali Khan',
                'email' => 'ali.khan@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'engineer',
                'engineer_code' => 'e1',
                'branch_id' => 1,
                'all_branches' => false,
                'phone' => '+971 50 333 4455',
                'cash_balance' => 5000.00,
                'zoho_petty_cash_account_id' => 'zoho_petty_ali_khan',
                'is_active' => true,
            ],
            [
                'name' => 'Meera Joseph',
                'email' => 'meera.joseph@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'accountant',
                'branch_id' => 1,
                'all_branches' => true,
                'phone' => '+971 50 444 5566',
                'cash_balance' => 0.00,
                'is_active' => true,
            ],
            // Additional Project Engineers
            [
                'name' => 'Ramesh Nair',
                'email' => 'ramesh.nair@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'engineer',
                'engineer_code' => 'e2',
                'branch_id' => 1,
                'all_branches' => false,
                'phone' => '+971 50 555 6677',
                'cash_balance' => 5000.00,
                'zoho_petty_cash_account_id' => 'zoho_petty_ramesh_nair',
                'is_active' => true,
            ],
            [
                'name' => 'Faisal Ahmed',
                'email' => 'faisal.ahmed@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'engineer',
                'engineer_code' => 'e3',
                'branch_id' => 1,
                'all_branches' => false,
                'phone' => '+971 50 666 7788',
                'cash_balance' => 5000.00,
                'zoho_petty_cash_account_id' => 'zoho_petty_faisal_ahmed',
                'is_active' => true,
            ],
            [
                'name' => 'Priya Menon',
                'email' => 'priya.menon@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'engineer',
                'engineer_code' => 'e4',
                'branch_id' => 2, // Abu Dhabi
                'all_branches' => false,
                'phone' => '+971 50 777 8899',
                'cash_balance' => 5000.00,
                'zoho_petty_cash_account_id' => 'zoho_petty_priya_menon',
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Thomas',
                'email' => 'sarah.thomas@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'engineer',
                'engineer_code' => 'e5',
                'branch_id' => 2, // Abu Dhabi
                'all_branches' => false,
                'phone' => '+971 50 888 9900',
                'cash_balance' => 5000.00,
                'zoho_petty_cash_account_id' => 'zoho_petty_sarah_thomas',
                'is_active' => true,
            ],
            [
                'name' => 'Omar Haddad',
                'email' => 'omar.haddad@richmount.ae',
                'password' => $defaultPassword,
                'role_name' => 'engineer',
                'engineer_code' => 'e6',
                'branch_id' => 3, // Sharjah
                'all_branches' => false,
                'phone' => '+971 50 999 0011',
                'cash_balance' => 5000.00,
                'zoho_petty_cash_account_id' => 'zoho_petty_omar_haddad',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            $roleName = $userData['role_name'];
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Sync Spatie Role
            $user->syncRoles([$roleName]);
        }
    }
}
