<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LeadController;
use App\Http\Controllers\QuotationController;

/*
|--------------------------------------------------------------------------
| Web Routes — Rich Mount ERP
|--------------------------------------------------------------------------
*/

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/switch-user', [LoginController::class, 'switchUser'])->name('auth.switch-user');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Users & Roles Management (Super Admin)
    Route::resource('users', UserController::class);

    // Branch Management
    Route::post('/switch-branch', [BranchController::class, 'switchBranch'])->name('branches.switch');
    Route::resource('branches', BranchController::class);

    // Profile & Password Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

    // Phase 2: Leads Management
    Route::post('/leads/bulk-assign', [LeadController::class, 'bulkAssign'])->name('leads.bulk-assign');
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::resource('leads', LeadController::class);

    // Phase 2: Quotations Workflow & Builder
    Route::get('/quotations/builder', [QuotationController::class, 'builder'])->name('quotations.builder');
    Route::post('/quotations/{quotation}/autosave', [QuotationController::class, 'autosave'])->name('quotations.autosave');
    Route::post('/quotations/{quotation}/clone', [QuotationController::class, 'clone'])->name('quotations.clone');
    Route::post('/quotations/{quotation}/submit', [QuotationController::class, 'submit'])->name('quotations.submit');
    Route::post('/quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
    Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
    Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send');
    Route::post('/quotations/{quotation}/award', [QuotationController::class, 'award'])->name('quotations.award');
    Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'downloadPdf'])->name('quotations.pdf');
    Route::resource('quotations', QuotationController::class)->except(['create', 'edit']);

    // Phase 3: Projects, PO, Work Completion, Invoicing
    Route::post('/projects/from-quote/{quotation}', [\App\Http\Controllers\ProjectController::class, 'createFromQuote'])->name('projects.create-from-quote');
    Route::post('/projects/{project}/po', [\App\Http\Controllers\ProjectController::class, 'recordPo'])->name('projects.record-po');
    Route::post('/projects/{project}/start-work', [\App\Http\Controllers\ProjectController::class, 'startWork'])->name('projects.start-work');
    Route::post('/projects/{project}/put-on-hold', [\App\Http\Controllers\ProjectController::class, 'putOnHold'])->name('projects.put-on-hold');
    Route::post('/projects/{project}/resume-work', [\App\Http\Controllers\ProjectController::class, 'resumeWork'])->name('projects.resume-work');
    Route::post('/projects/{project}/complete-work', [\App\Http\Controllers\ProjectController::class, 'completeWork'])->name('projects.complete-work');
    Route::resource('projects', \App\Http\Controllers\ProjectController::class);

    // Purchase Orders (Vendor)
    Route::post('/projects/{project}/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('po.store');
    Route::get('/purchase-orders/{purchaseOrder}/pdf', [\App\Http\Controllers\PurchaseOrderController::class, 'downloadPdf'])->name('po.pdf');

    // Work Completion Certificates
    Route::get('/work-completion-certificates/{wcc}/pdf', [\App\Http\Controllers\WorkCompletionCertificateController::class, 'downloadPdf'])->name('wcc.pdf');

    // Invoices & Payments
    Route::post('/projects/{project}/invoices', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
    Route::post('/invoices/{invoice}/payment', [\App\Http\Controllers\InvoiceController::class, 'recordPayment'])->name('invoices.payment');
    Route::get('/invoices/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // Phase 4: Expenses & Internal Transfers
    Route::get('/expenses', [\App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [\App\Http\Controllers\ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/ledger/{user}', [\App\Http\Controllers\ExpenseController::class, 'ledger'])->name('expenses.ledger');
    Route::post('/transfers', [\App\Http\Controllers\TransferController::class, 'store'])->name('transfers.store');
    Route::put('/transfers/{transfer}', [\App\Http\Controllers\TransferController::class, 'update'])->name('transfers.update');

    // Phase 4: Finance Approval Feed & Center
    Route::get('/finance', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/expenses/{expense}/approve', [\App\Http\Controllers\FinanceController::class, 'approveExpense'])->name('finance.expenses.approve');
    Route::post('/finance/expenses/{expense}/reject', [\App\Http\Controllers\FinanceController::class, 'rejectExpense'])->name('finance.expenses.reject');
    Route::post('/finance/transfers/{transfer}/approve', [\App\Http\Controllers\FinanceController::class, 'approveTransfer'])->name('finance.transfers.approve');
    Route::post('/finance/transfers/{transfer}/reject', [\App\Http\Controllers\FinanceController::class, 'rejectTransfer'])->name('finance.transfers.reject');

    // Phase 4: Reports & Analytics
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');

    // Phase 6: Zoho Books Integration & Sync Center
    Route::get('/zoho', [\App\Http\Controllers\ZohoController::class, 'index'])->name('zoho.index');
    Route::post('/zoho/sync', [\App\Http\Controllers\ZohoController::class, 'syncNow'])->name('zoho.sync');
    Route::post('/zoho/logs/{log}/retry', [\App\Http\Controllers\ZohoController::class, 'retry'])->name('zoho.retry');

    // Settings & Display Preferences
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings/approver', [\App\Http\Controllers\SettingController::class, 'updateApprover'])->name('settings.approver');
});
