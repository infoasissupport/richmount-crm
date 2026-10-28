<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login screen.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Process authentication request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'This account has been deactivated. Please contact an administrator.']);
            }

            $request->session()->regenerate();

            AuditService::log('login', $user, 'User signed in successfully', null, null, $user->id);

            return redirect()->intended(route('dashboard'))
                ->with('toast_success', 'Welcome back, ' . $user->name);
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'These credentials do not match our records.']);
    }

    /**
     * Fast demo switcher (for reviewing roles in pairing/demo sessions).
     */
    public function switchUser(Request $request): RedirectResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $user = User::findOrFail($request->user_id);
        Auth::login($user);
        $request->session()->regenerate();

        AuditService::log('switch_user', $user, 'Switched to demo role: ' . $user->role_name, null, null, $user->id);

        return redirect()->back()->with('toast_info', 'Switched to ' . $user->name . ' (' . $user->role_label . ')');
    }

    /**
     * Destroy user session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditService::log('logout', $user, 'User signed out', null, null, $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('toast_info', 'You have been signed out.');
    }
}
