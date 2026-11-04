@extends('layouts.app')

@section('title', 'Vehicles')
@section('page_id', 'vehicles')

@section('breadcrumbs')
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <a class="crumb" href="{{ route('expenses.index') }}">Expenses</a>
    <svg class="ic sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-chevron-right"/></svg>
    <span class="crumb current">Vehicles</span>
@endsection

@section('content')
<section class="page-head" id="pageHead">
    <div>
        <h1>Vehicles</h1>
        <p class="sub">Active vehicles are available for Fuel expenses.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-ghost" href="{{ route('expenses.index') }}">
            <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow-left"/></svg>
            Back to Expenses
        </a>
    </div>
</section>

<div class="dash-scroll">
    <section class="card">
        <header class="card-head"><h2>Add Vehicle</h2></header>
        <form action="{{ route('vehicles.store') }}" method="POST" class="form-grid">
            @csrf
            <div class="field">
                <label class="label req" for="newVehicleName">Vehicle Name</label>
                <input class="input" id="newVehicleName" name="name" required maxlength="100" placeholder="e.g. Site Pickup 01">
            </div>
            <div class="field">
                <label class="label req" for="newVehicleRegistration">Registration Number</label>
                <input class="input" id="newVehicleRegistration" name="registration_number" required maxlength="32" placeholder="e.g. DXB A 12345">
            </div>
            <div class="field">
                <label class="label" for="newVehicleModel">Make / Model</label>
                <input class="input" id="newVehicleModel" name="make_model" maxlength="100" placeholder="e.g. Toyota Hilux">
            </div>
            <div class="field" style="justify-content: flex-end;">
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                    Add Vehicle
                </button>
            </div>
        </form>
    </section>

    <section class="card">
        <header class="card-head"><h2>Vehicle Register</h2></header>
        <div class="table-wrap page">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vehicle Name</th>
                        <th>Registration Number</th>
                        <th>Make / Model</th>
                        <th>Status</th>
                        <th class="ta-c">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                        <tr>
                            <td colspan="5">
                                <form action="{{ route('vehicles.update', $vehicle) }}" method="POST" class="vehicle-edit-row">
                                    @csrf
                                    @method('PUT')
                                    <input class="input" name="name" aria-label="Vehicle name" required maxlength="100" value="{{ $vehicle->name }}">
                                    <input class="input" name="registration_number" aria-label="Registration number" required maxlength="32" value="{{ $vehicle->registration_number }}">
                                    <input class="input" name="make_model" aria-label="Make and model" maxlength="100" value="{{ $vehicle->make_model }}">
                                    <label class="check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" {{ $vehicle->is_active ? 'checked' : '' }}>
                                        Active
                                    </label>
                                    <button class="btn btn-ghost btn-sm" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="ta-c empty">No vehicles have been added.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $vehicles->withQueryString()->links() }}
    </section>
</div>
@endsection