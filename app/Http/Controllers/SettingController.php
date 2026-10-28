<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    /**
     * Display settings and preferences page.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class); // Super Admin or users with settings.view

        $branchesCount = Branch::count();
        $auditCount = AuditLog::count();
        $quoteApprover = cache()->get('setting_quote_approver', 'estimation');
        $users = User::with('branch')->where('is_active', true)->get();

        return view('settings.index', compact('branchesCount', 'auditCount', 'quoteApprover', 'users'));
    }

    /**
     * Update quotation approver setting.
     */
    public function updateApprover(Request $request): RedirectResponse
    {
        $request->validate([
            'quote_approver' => 'required|in:estimation,admin',
        ]);

        cache()->forever('setting_quote_approver', $request->quote_approver);

        return back()->with('toast_success', 'Quotation approver setting updated successfully.');
    }
}
