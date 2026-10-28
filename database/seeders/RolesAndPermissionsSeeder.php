<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view' => 'View dashboard',

            'leads.view' => 'View leads',
            'leads.create' => 'Create leads',
            'leads.edit' => 'Edit leads',
            'leads.assign' => 'Assign engineers',
            'leads.delete' => 'Delete leads',

            'quotes.view' => 'View quotations',
            'quotes.create' => 'Create / clone quotations',
            'quotes.edit' => 'Edit draft quotations',
            'quotes.submit' => 'Submit quotations for approval',
            'quotes.approve' => 'Approve / reject quotations',
            'quotes.send' => 'Send quotations to customer',
            'quotes.award' => 'Mark quotations awarded',

            'projects.view' => 'View projects',
            'projects.create' => 'Create projects',
            'projects.open' => 'Record PO / open project',
            'projects.edit' => 'Update project status & work completion',

            'expenses.view' => 'View expenses & cash in hand',
            'expenses.create' => 'Record expenses (OCR)',
            'expenses.approve' => 'Approve / reject expenses',
            'payments.create' => 'Record customer payments',

            'transfers.view' => 'View internal transfers',
            'transfers.create' => 'Create internal transfers',
            'transfers.edit' => 'Edit submitted transfers (Accountant only)',
            'transfers.approve' => 'Approve / reject transfers',

            'transactions.view' => 'View approval center',
            'transactions.approve' => 'Approve transactions for Zoho Books',

            'invoices.view' => 'View invoices',
            'invoices.create' => 'Create invoices',
            'invoices.submit' => 'Submit invoices / close project',

            'reports.view' => 'View reports',
            'reports.export' => 'Download / print reports',

            'zoho.view' => 'View Zoho Books status',
            'zoho.sync' => 'Sync / retry Zoho Books',

            'users.view' => 'View users',
            'users.edit' => 'Manage users',
            'roles.view' => 'View roles & permissions',
            'roles.edit' => 'Manage roles',

            'branches.view' => 'View branches',
            'branches.edit' => 'Manage branches',

            'settings.view' => 'View settings',
            'settings.edit' => 'Manage settings',
            'audit.view' => 'View audit log',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 1. Super Admin: All permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Estimation User
        $estimation = Role::firstOrCreate(['name' => 'estimation', 'guard_name' => 'web']);
        $estimation->syncPermissions([
            'dashboard.view',
            'leads.view', 'leads.create', 'leads.edit', 'leads.assign', 'leads.delete',
            'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.submit', 'quotes.send', 'quotes.award',
            'projects.view', 'projects.edit',
            'reports.view', 'reports.export',
        ]);

        // 3. Project Engineer
        $engineer = Role::firstOrCreate(['name' => 'engineer', 'guard_name' => 'web']);
        $engineer->syncPermissions([
            'dashboard.view',
            'leads.view',
            'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.submit',
            'projects.view',
            'expenses.view', 'expenses.create',
            'payments.create',
            'transfers.view', 'transfers.create',
            'reports.view', 'reports.export',
        ]);

        // 4. Accountant
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions([
            'dashboard.view',
            'quotes.view',
            'projects.view', 'projects.create', 'projects.open',
            'expenses.view', 'expenses.approve',
            'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.approve',
            'transactions.view', 'transactions.approve',
            'invoices.view', 'invoices.create', 'invoices.submit',
            'reports.view', 'reports.export',
            'zoho.view', 'zoho.sync',
        ]);
    }
}
