<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('expenses.approve'), 403);

        $vehicles = Vehicle::orderBy('registration_number')->paginate(20);

        return view('vehicles.index', compact('vehicles'));
    }

    public function store(VehicleRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['name'] = ($validated['name'] ?? '') ?: $validated['registration_number'];
        $vehicle = Vehicle::create($validated + [
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::log('created', $vehicle, "Added vehicle {$vehicle->registration_number}", null, $vehicle->toArray());

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'vehicle' => $vehicle], 201);
        }

        return redirect()->route('vehicles.index')->with('toast_success', "Vehicle {$vehicle->registration_number} added.");
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $oldValues = $vehicle->toArray();
        $validated = $request->validated();
        $validated['name'] = ($validated['name'] ?? '') ?: $validated['registration_number'];
        $vehicle->update($validated + [
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditService::log('updated', $vehicle, "Updated vehicle {$vehicle->registration_number}", $oldValues, $vehicle->fresh()->toArray());

        return redirect()->route('vehicles.index')->with('toast_success', "Vehicle {$vehicle->registration_number} updated.");
    }
}
