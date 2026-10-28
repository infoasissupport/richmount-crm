<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the Super Admin registration form.
     */
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming Super Admin registration request.
     * Public registration exclusively provisions Super Admin accounts.
     * Other user accounts (Estimation, Engineer, Accountant) are created internally in Users & Roles.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $defaultBranch = Branch::first();

        // Enforce Super Admin role with all branches access
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role_name' => 'super_admin',
            'branch_id' => $defaultBranch?->id ?? 1,
            'all_branches' => true,
            'is_active' => true,
        ]);

        $user->syncRoles(['super_admin']);

        AuditService::log('register', $user, "New Super Admin registered: {$user->name}", null, null, $user->id);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('toast_success', 'Welcome, ' . $user->name . '! Your Super Admin account has been created.');
    }
}
