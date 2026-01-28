<div class="mb-3">
    <form id="filter-form" class="row g-1">
        <div class="col-md-2">
            <select id="eventFilter" class="form-select form-select-sm select2">
                <option value="">All Events</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="restored">Restored</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="modelFilter" class="form-select form-select-sm select2">
                <option value="">All Models</option>
                <option value="App\Models\Logistic\Inventory">Inventory</option>
                <option value="App\Models\Logistic\MaterialRequest">Material Request</option>
                <option value="App\Models\Logistic\GoodsOut">Goods Out</option>
                <option value="App\Models\Logistic\GoodsIn">Goods In</option>
                <option value="App\Models\Production\Project">Project</option>
                <option value="App\Models\Production\ProjectPart">Project Part</option>
                <option value="App\Models\Admin\User">User</option>
                <option value="App\Models\Hr\Employee">Employee</option>
                <option value="App\Models\Finance\Currency">Currency</option>
                <option value="App\Models\Procurement\PurchaseRequest">Purchase Request</option>
                <option value="App\Models\Production\MaterialPlanning">Material Planning</option>
                <option value="App\Models\Procurement\Supplier">Supplier</option>
                <option value="App\Models\Hr\LeaveRequest">Leave Request</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" id="dateFrom" class="form-control form-control-sm"
                placeholder="From Date">
        </div>
        <div class="col-md-2">
            <input type="date" id="dateTo" class="form-control form-control-sm" placeholder="To Date">
        </div>
        <div class="col-md-2">
            <input type="text" id="custom-search" class="form-control form-control-sm"
                placeholder="Search...">
        </div>
        <div class="col-md-1">
            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm w-100"
                title="Reset All Filters">
                <i class="fas fa-times me-1"></i> Reset
            </button>
        </div>
    </form>
</div>