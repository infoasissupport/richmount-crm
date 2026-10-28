<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\InvoiceApiController;
use App\Http\Controllers\Api\V1\LeadApiController;
use App\Http\Controllers\Api\V1\ProjectApiController;
use App\Http\Controllers\Api\V1\QuotationApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Version 1
|--------------------------------------------------------------------------
| Mobile Flutter App & External Services
*/

Route::prefix('v1')->group(function () {
    // Public Authentication
    Route::post('/auth/login', [AuthApiController::class, 'login']);

    // Protected API Endpoints (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/user/me', [AuthApiController::class, 'me']);
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);

        // Leads Mobile API
        Route::get('/leads', [LeadApiController::class, 'index']);
        Route::post('/leads', [LeadApiController::class, 'store']);
        Route::get('/leads/{lead}', [LeadApiController::class, 'show']);
        Route::put('/leads/{lead}', [LeadApiController::class, 'update']);
        Route::post('/leads/{lead}/assign', [LeadApiController::class, 'assign']);

        // Quotations Mobile API
        Route::get('/quotations', [QuotationApiController::class, 'index']);
        Route::post('/quotations', [QuotationApiController::class, 'store']);
        Route::get('/quotations/{quotation}', [QuotationApiController::class, 'show']);
        Route::post('/quotations/{quotation}/autosave', [QuotationApiController::class, 'autosave']);
        Route::post('/quotations/{quotation}/clone', [QuotationApiController::class, 'clone']);
        Route::post('/quotations/{quotation}/submit', [QuotationApiController::class, 'submit']);
        Route::post('/quotations/{quotation}/approve', [QuotationApiController::class, 'approve']);
        Route::post('/quotations/{quotation}/reject', [QuotationApiController::class, 'reject']);
        Route::post('/quotations/{quotation}/send', [QuotationApiController::class, 'send']);
        Route::post('/quotations/{quotation}/award', [QuotationApiController::class, 'award']);

        // Projects Mobile API
        Route::get('/projects', [ProjectApiController::class, 'index']);
        Route::post('/projects', [ProjectApiController::class, 'store']);
        Route::get('/projects/{project}', [ProjectApiController::class, 'show']);
        Route::post('/projects/{project}/po', [ProjectApiController::class, 'recordPo']);
        Route::post('/projects/{project}/complete-work', [ProjectApiController::class, 'completeWork']);

        // Invoices Mobile API
        Route::get('/invoices', [InvoiceApiController::class, 'index']);
        Route::post('/projects/{project}/invoices', [InvoiceApiController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceApiController::class, 'show']);
        Route::post('/invoices/{invoice}/payment', [InvoiceApiController::class, 'recordPayment']);

        // Expenses Mobile API
        Route::get('/expenses', [\App\Http\Controllers\Api\V1\ExpenseApiController::class, 'index']);
        Route::post('/expenses', [\App\Http\Controllers\Api\V1\ExpenseApiController::class, 'store']);
        Route::get('/expenses/{expense}', [\App\Http\Controllers\Api\V1\ExpenseApiController::class, 'show']);

        // Transfers Mobile API
        Route::get('/transfers', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'index']);
        Route::post('/transfers', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'store']);
    });
});
