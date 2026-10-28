<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users, roles, and audit trail.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('branch')
            ->when($request->filled('role'), fn($q) => $q->where('role_name', $request->role))
            ->when($request->filled('branch_id'), fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn($sq) => $sq->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(15);

        $branches = Branch::active()->get();
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $auditLogs = AuditLog::with('user')->latest()->take(20)->get();

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'branches' => $branches,
            ]);
        }

        return view('users.index', compact('users', 'branches', 'roles', 'permissions', 'auditLogs'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['all_branches'] = $request->boolean('all_branches');
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);
        $user->syncRoles([$validated['role_name']]);

        AuditService::log('created', $user, "Created user {$user->name} with role {$user->role_label}", null, $user->toArray());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User {$user->name} created successfully.",
                'user' => $user->load('branch'),
            ], 201);
        }

        return redirect()->route('users.index')->with('toast_success', "User {$user->name} created successfully.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $old = $user->toArray();
        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['all_branches'] = $request->boolean('all_branches');
        $validated['is_active'] = $request->boolean('is_active', true);

        $user->update($validated);
        $user->syncRoles([$validated['role_name']]);

        AuditService::log('updated', $user, "Updated user {$user->name}", $old, $user->fresh()->toArray());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User {$user->name} updated successfully.",
                'user' => $user->fresh()->load('branch'),
            ]);
        }

        return redirect()->route('users.index')->with('toast_success', "User {$user->name} updated successfully.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $user);

        $name = $user->name;
        $old = $user->toArray();

        $user->delete();

        AuditService::log('deleted', $user, "Deleted user {$name}", $old, null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User {$name} deleted.",
            ]);
        }

        return redirect()->route('users.index')->with('toast_info', "User {$name} deleted.");
    }
}
