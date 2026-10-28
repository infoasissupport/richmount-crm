<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display a listing of branches and branch engineers.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        $branches = Branch::withCount(['users', 'engineers'])->latest()->get();
        $engineers = User::where('role_name', 'engineer')->with('branch')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'branches' => $branches,
                'engineers' => $engineers,
            ]);
        }

        return view('branches.index', compact('branches', 'engineers'));
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $branch = Branch::create($validated);

        AuditService::log('created', $branch, "Created branch {$branch->name} ({$branch->code})", null, $branch->toArray());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Branch {$branch->name} created.",
                'branch' => $branch,
            ], 201);
        }

        return redirect()->route('branches.index')->with('toast_success', "Branch {$branch->name} created successfully.");
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse|JsonResponse
    {
        $old = $branch->toArray();
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $branch->update($validated);

        AuditService::log('updated', $branch, "Updated branch {$branch->name}", $old, $branch->fresh()->toArray());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Branch {$branch->name} updated.",
                'branch' => $branch->fresh(),
            ]);
        }

        return redirect()->route('branches.index')->with('toast_success', "Branch {$branch->name} updated successfully.");
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Request $request, Branch $branch): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $branch);

        if ($branch->users()->count() > 0) {
            $msg = "Cannot delete branch {$branch->name} because active users are assigned to it.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('branches.index')->with('toast_error', $msg);
        }

        $name = $branch->name;
        $old = $branch->toArray();
        $branch->delete();

        AuditService::log('deleted', $branch, "Deleted branch {$name}", $old, null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Branch {$name} deleted.",
            ]);
        }

        return redirect()->route('branches.index')->with('toast_info', "Branch {$name} deleted.");
    }
}
