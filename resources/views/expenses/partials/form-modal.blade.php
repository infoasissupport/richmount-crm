<div class="modal-wrap expense-modal" id="expenseModal" data-auto-open="{{ $autoOpen ? '1' : '0' }}" data-selected-project-id="{{ $selectedProjectId ?? '' }}" data-vehicle-store-url="{{ route('vehicles.store') }}" aria-hidden="true" onclick="if(event.target === this) closeExpenseModal()">
    <div class="modal modal-lg project-expense-form" role="dialog" aria-modal="true" aria-labelledby="expenseModalTitle">
        <div class="modal-head">
            <div>
                <h3 id="expenseModalTitle">Add Expense</h3>
                @if($projectContext)
                    <p>Project expenses need an officially opened project. Fuel and vehicle maintenance are booked to a vehicle.</p>
                @else
                    <p>Log petty cash or card expense with receipt verification</p>
                @endif
            </div>
            <button class="icon-btn" type="button" onclick="closeExpenseModal()" aria-label="Close">
                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
            </button>
        </div>

        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="field">
                        <label class="label req" for="expCat">Expense Type</label>
                        <select class="select" id="expCat" name="category" required>
                            <option value="materials">Materials</option>
                            <option value="transport">Transport / Delivery</option>
                            <option value="labor">Daily Labor</option>
                            <option value="meals">Site Meals</option>
                            <option value="tools">Tools &amp; Equipment</option>
                            <option value="fuel">Fuel &amp; Petrol</option>
                            <option value="permits">Municipality &amp; Permits</option>
                            <option value="miscellaneous">Miscellaneous</option>
                        </select>
                    </div>

                    <div class="field" id="expVehicleField" hidden>
                        <label class="label req" for="expVehicle">Vehicle</label>
                        <select class="select" id="expVehicle" name="vehicle_id" disabled>
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                            @endforeach
                            @can('expenses.approve')
                                <option value="__add_new_vehicle__">+ Add New Vehicle...</option>
                            @endcan
                        </select>
                        @can('expenses.approve')
                            <div class="vehicle-quick-add" id="expNewVehicleField" hidden>
                                <label class="label" for="expNewVehicleRegistration">New Vehicle Number</label>
                                <div class="field-inline">
                                    <input class="input" id="expNewVehicleRegistration" type="text" maxlength="32" placeholder="e.g. DXB A 12345" onkeydown="if(event.key === 'Enter'){event.preventDefault(); addVehicleFromExpense();}">
                                    <button class="btn btn-soft btn-sm" id="addExpenseVehicleButton" type="button" onclick="addVehicleFromExpense()">
                                        <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-plus"/></svg>
                                        Add
                                    </button>
                                </div>
                                <span class="hint" id="expNewVehicleMessage" aria-live="polite"></span>
                            </div>
                        @endcan
                        @if(!auth()->user()->can('expenses.approve') && $vehicles->isEmpty())
                            <span class="hint">No active vehicles are configured. Ask an Accountant to add one.</span>
                        @endif
                    </div>

                    <div class="field span-2">
                        <label class="label req" for="expProject">Project</label>
                        <select class="select" id="expProject" name="project_id">
                            @if($projectContext)
                                <option value="{{ $projectContext->id }}" selected>{{ $projectContext->project_number }} · {{ $projectContext->name }}</option>
                            @else
                                <option value="">General Overhead (No Project)</option>
                                @foreach($openProjects as $openProject)
                                    <option value="{{ $openProject->id }}" {{ (string) $selectedProjectId === (string) $openProject->id ? 'selected' : '' }}>{{ $openProject->project_number }} · {{ $openProject->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="field span-2">
                        <label class="label req">Payment Method</label>
                        <div class="seg project-expense-method">
                            <label>
                                <input id="expTypeCash" name="type" type="radio" value="cash" checked>
                                <span><svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>Cash</span>
                            </label>
                            <label>
                                <input id="expTypeCard" name="type" type="radio" value="card">
                                <span><svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-credit-card"/></svg>Company Card</span>
                            </label>
                        </div>
                    </div>

                    <div class="field span-2">
                        <label class="label" for="expReceipt">Receipt</label>
                        <div class="receipt-row">
                            <input class="input" id="expReceipt" name="receipt" type="file" accept=".pdf,.png,.jpg,.jpeg" aria-describedby="expReceiptHint">
                            <button class="btn btn-soft btn-sm" type="button" onclick="document.getElementById('expReceipt').click()">
                                <svg class="ic sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-scan"/></svg>
                                Scan Receipt
                            </button>
                        </div>
                        <span class="hint" id="expReceiptHint">Receipt processing is simulated in this prototype.</span>
                    </div>

                    <div class="field">
                        <label class="label" for="expVendor">Vendor</label>
                        <input class="input" id="expVendor" name="vendor_name" type="text" placeholder="e.g. Danube Building Materials">
                    </div>

                    <div class="field">
                        <label class="label req" for="expSubtotal">Amount (AED)</label>
                        <input class="input" id="expSubtotal" name="subtotal" type="number" step="0.01" min="0.01" required placeholder="0.00">
                    </div>

                    <div class="field">
                        <label class="label req" for="expDate">Date</label>
                        <input class="input" id="expDate" name="date" type="date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="field">
                        <label class="label" for="expBillNumber">Receipt Reference</label>
                        <input class="input" id="expBillNumber" name="bill_number" type="text" placeholder="Invoice / Receipt No.">
                    </div>

                    <div class="field span-2">
                        <label class="label" for="expDesc">Description</label>
                        <textarea class="textarea" id="expDesc" name="description" rows="3" placeholder="What was purchased and why"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" onclick="closeExpenseModal()">Cancel</button>
                <button class="btn btn-primary" type="submit">
                    <svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-send"/></svg>
                    Submit for Approval
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openExpenseModal(selectedProjectId = null) {
        const expenseModal = document.getElementById('expenseModal');
        const projectSelect = document.getElementById('expProject');
        const projectId = selectedProjectId ?? expenseModal.dataset.selectedProjectId;

        if (projectSelect && projectId) {
            projectSelect.value = projectId;
        }

        expenseModal.classList.add('open');
        expenseModal.setAttribute('aria-hidden', 'false');
    }

    function closeExpenseModal() {
        const expenseModal = document.getElementById('expenseModal');
        expenseModal.classList.remove('open');
        expenseModal.setAttribute('aria-hidden', 'true');
    }

    const expenseCategorySelect = document.getElementById('expCat');
    const vehicleField = document.getElementById('expVehicleField');
    const vehicleSelect = document.getElementById('expVehicle');
    const newVehicleField = document.getElementById('expNewVehicleField');
    const newVehicleInput = document.getElementById('expNewVehicleRegistration');
    const newVehicleMessage = document.getElementById('expNewVehicleMessage');
    const addExpenseVehicleButton = document.getElementById('addExpenseVehicleButton');

    function syncFuelVehicleField() {
        const requiresVehicle = expenseCategorySelect.value === 'fuel';
        vehicleField.hidden = !requiresVehicle;
        vehicleSelect.disabled = !requiresVehicle;
        vehicleSelect.required = requiresVehicle;

        if (!requiresVehicle) {
            vehicleSelect.value = '';
            if (newVehicleField) newVehicleField.hidden = true;
        }
    }

    vehicleSelect.addEventListener('change', function () {
        if (vehicleSelect.value === '__add_new_vehicle__') {
            vehicleSelect.value = '';
            newVehicleField.hidden = false;
            newVehicleMessage.textContent = '';
            newVehicleInput.focus();
        } else if (newVehicleField) {
            newVehicleField.hidden = true;
        }
    });

    expenseCategorySelect.addEventListener('change', syncFuelVehicleField);
    syncFuelVehicleField();

    async function addVehicleFromExpense() {
        const registrationNumber = newVehicleInput.value.trim();
        if (!registrationNumber) {
            newVehicleMessage.textContent = 'Enter a vehicle number.';
            newVehicleInput.focus();
            return;
        }

        addExpenseVehicleButton.disabled = true;
        newVehicleMessage.textContent = '';

        try {
            const response = await fetch(expenseModal.dataset.vehicleStoreUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ registration_number: registrationNumber }),
            });
            const result = await response.json();

            if (!response.ok) {
                const errorMessage = Object.values(result.errors ?? {}).flat()[0];
                newVehicleMessage.textContent = errorMessage ?? result.message ?? 'Could not add this vehicle.';
                return;
            }

            const option = document.createElement('option');
            option.value = result.vehicle.id;
            option.textContent = result.vehicle.registration_number;
            vehicleSelect.add(option, vehicleSelect.options[vehicleSelect.options.length - 1]);
            vehicleSelect.value = String(result.vehicle.id);
            newVehicleField.hidden = true;
            newVehicleInput.value = '';
        } catch (error) {
            newVehicleMessage.textContent = 'Could not add this vehicle. Please try again.';
        } finally {
            addExpenseVehicleButton.disabled = false;
        }
    }

    if (document.getElementById('expenseModal')?.dataset.autoOpen === '1') {
        openExpenseModal();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeExpenseModal();
    });
</script>
@endpush