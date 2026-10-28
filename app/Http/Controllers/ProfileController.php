<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the user profile.
     */
    public function edit(): View
    {
        $user = Auth::user()->load('branch');
        $permissions = $user->getAllPermissions();
        $demoUsers = User::with('branch')->whereIn('role_name', ['super_admin', 'estimation', 'engineer', 'accountant'])->get();

        return view('profile.index', compact('user', 'permissions', 'demoUsers'));
    }

    /**
     * Update basic user details.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $old = $user->only(['name', 'phone']);
        $user->update($validated);

        AuditService::log('updated', $user, 'Updated profile details', $old, $validated);

        return redirect()->route('profile.edit')->with('toast_success', 'Profile details updated.');
    }

    /**
     * Update the user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditService::log('password_reset', $user, 'User changed account password');

        return redirect()->route('profile.edit')->with('toast_success', 'Password updated successfully.');
    }
}
