# iSyment - Mini ERP DCM

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Sistem manajemen inventori berbasis Laravel 11 yang pada saat ini fokus pada material requests, goods tracking, dan project costing. Sistem ini mengelola siklus lengkap dari permintaan material hingga operasi keluar-masuk barang dengan notifikasi real-time.

## 📋 Daftar Isi

-   [Fitur Utama](#-fitur-utama)
-   [Teknologi Stack](#-teknologi-stack)
-   [Persyaratan Sistem](#-persyaratan-sistem)
-   [Instalasi](#-instalasi)
-   [Konfigurasi](#-konfigurasi)
-   [Struktur Database](#-struktur-database)
-   [Alur Kerja Sistem](#-alur-kerja-sistem)
-   [Modul & Fitur](#-modul--fitur)
-   [Role & Permissions](#-role--permissions)
-   [API & Broadcasting](#-api--broadcasting)
-   [Export & Reporting](#-export--reporting)
-   [Troubleshooting](#-troubleshooting)
-   [Contributing](#-contributing)

## 🎯 Fitur Utama

### Material Management

-   ✅ Katalog inventori dengan multi-currency support
-   ✅ Tracking stock real-time dengan lock mechanism
-   ✅ Material request workflow (pending → approved → delivered)
-   ✅ Bulk operations untuk efisiensi
-   ✅ QR Code generation untuk tracking

### Goods Operations

-   ✅ Goods Out (pengeluaran barang untuk project)
-   ✅ Goods In (pengembalian barang dari project)
-   ✅ Material Usage tracking otomatis
-   ✅ Integration dengan project costing

### Project Management

-   ✅ Project tracking dengan status & timeline
-   ✅ Department assignment
-   ✅ Material usage per project
-   ✅ Cost calculation dengan freight costs
-   ✅ Project summary & reports

### Advanced Features

-   ✅ Real-time notifications dengan Pusher
-   ✅ Role-based access control (RBAC)
-   ✅ Audit trail dengan owen-it/auditing
-   ✅ Excel export untuk semua modul
-   ✅ Material planning & forecasting

## 🛠 Teknologi Stack

### Backend

-   **Framework**: Laravel 11.x
-   **PHP**: 8.1+
-   **Database**: MySQL 8.0+
-   **Broadcasting**: Pusher
-   **Package Manager**: Composer

### Frontend

-   **UI Framework**: Bootstrap 5.3
-   **JavaScript**: jQuery 3.6
-   **DataTables**: Server-side processing
-   **Select2**: Advanced select boxes
-   **SweetAlert2**: Beautiful alerts
-   **Chart.js**: Data visualization

### Key Laravel Packages

```json
{
    "yajra/laravel-datatables": "^10.0",
    "maatwebsite/excel": "^3.1",
    "owen-it/laravel-auditing": "^13.0",
    "simplesoftwareio/simple-qrcode": "^4.2",
    "pusher/pusher-php-server": "^7.2"
}
```

## 💻 Persyaratan Sistem

-   PHP >= 8.1
-   Composer 2.x
-   Node.js >= 18.x & NPM
-   MySQL >= 8.0
-   Extension PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

## 📦 Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd inventory-system-v2-upg-larv
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup

```bash
# Buat database MySQL
mysql -u root -p
CREATE DATABASE inventory_db_upg_larv;
exit;

# Atau import SQL file yang sudah ada
mysql -u root -p inventory_db_upg_larv < inventory_db_upg_larv.sql

# Jalankan migrasi (jika menggunakan fresh install)
php artisan migrate:fresh --seed
```

### 5. Storage Link

```bash
php artisan storage:link
```

### 6. Compile Assets

```bash
# Development
npm run dev

# Production
npm run build

# Watch mode (untuk development)
npm run watch
```

### 7. Start Development Server

```bash
php artisan serve
```

Aplikasi dapat diakses di `http://localhost:8000`

## ⚙️ Konfigurasi

### .env Configuration

```env
# Application
APP_NAME="iSyment Inventory"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_db_upg_larv
DB_USERNAME=root
DB_PASSWORD=

# Broadcasting (Pusher)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=ap1

# Mail (untuk notifikasi)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Pusher Setup

1. Daftar di [pusher.com](https://pusher.com)
2. Buat channel baru
3. Copy credentials ke `.env`
4. Uncomment `Broadcast::routes()` di `routes/channels.php`

## 🗄️ Struktur Database

### Core Tables

#### `inventories`

```sql
- id: Primary Key
- name: Material name
- quantity: Current stock
- price: Base price
- domestic_freight: Biaya freight domestik
- international_freight: Biaya freight internasional
- unit: Satuan (kg, pcs, m, dll)
- currency_id: Foreign Key ke currencies
- category_id: Foreign Key ke categories
- supplier_id: Foreign Key ke suppliers
- timestamps, soft_deletes
```

#### `material_requests`

```sql
- id: Primary Key
- inventory_id: Foreign Key
- project_id: Foreign Key (nullable)
- user_id: Foreign Key (requester)
- qty: Jumlah diminta
- processed_qty: Jumlah yang sudah diproses
- status: pending|approved|delivered|canceled
- requested_at: Tanggal request
- timestamps, soft_deletes
```

#### `goods_out`

```sql
- id: Primary Key
- inventory_id: Foreign Key
- project_id: Foreign Key (nullable)
- user_id: Foreign Key (assigned user)
- quantity: Jumlah keluar
- issued_by: Username yang mengeluarkan
- issued_at: Tanggal keluar
- remark: Catatan
- timestamps, soft_deletes
```

#### `goods_in`

```sql
- id: Primary Key
- goods_out_id: Foreign Key (nullable untuk independent)
- inventory_id: Foreign Key
- project_id: Foreign Key (nullable)
- quantity: Jumlah masuk kembali
- returned_by: Username yang mengembalikan
- returned_at: Tanggal kembali
- remark: Catatan
- timestamps, soft_deletes
```

#### `material_usages`

```sql
- id: Primary Key
- inventory_id: Foreign Key
- project_id: Foreign Key (nullable)
- used_quantity: Jumlah terpakai (calculated)
- timestamps, soft_deletes
```

#### `projects`

```sql
- id: Primary Key
- name: Nama project
- qty: Jumlah unit/target
- department_id: Foreign Key
- project_status_id: Foreign Key
- start_date: Tanggal mulai
- deadline: Target selesai
- finish_date: Tanggal selesai aktual
- img: Project image (nullable)
- created_by: Username creator
- timestamps, soft_deletes
```

### Supporting Tables

-   `users`: User management dengan role
-   `departments`: Departemen/divisi
-   `categories`: Kategori material
-   `suppliers`: Data supplier
-   `currencies`: Multi-currency dengan exchange rate
-   `project_statuses`: Status project (active, completed, dll)
-   `project_parts`: Part/komponen project
-   `audits`: Audit trail (owen-it/auditing)

## 🔄 Alur Kerja Sistem

### 1. Material Request Flow

```
User Request → Logistic Approve → Goods Out Created → Material Used
     ↓                ↓                     ↓                ↓
  pending          approved             delivered         completed
```

**Detail Proses**:

1. User membuat material request dengan qty yang dibutuhkan
2. System validasi stock availability dengan `lockForUpdate()`
3. Super Admin/Logistic Admin approve request
4. Goods Out dibuat otomatis atau manual
5. Material Usage recorded otomatis via helper
6. Status diupdate dan trigger broadcasting event

### 2. Goods Out/In Flow

```
Goods Out → Project Usage → Goods In (Return) → Stock Updated
    ↓              ↓                 ↓                  ↓
Material    Calculate Usage    Update Usage      Sync Stock
```

**Detail Proses**:

1. Goods Out mengurangi stock inventory
2. Material Usage dihitung: `used_qty = goods_out_qty - goods_in_qty`
3. Goods In menambah kembali stock (untuk material yang tidak terpakai)
4. `MaterialUsageHelper::sync()` update usage real-time

### 3. Project Costing Flow

```
Material Usage Data → Calculate Costs → Generate Report
        ↓                    ↓                  ↓
  Per Project      Unit Cost + Freight    Excel Export
```

**Rumus Perhitungan**:

```php
Total Unit Cost = Price + Domestic Freight + International Freight
Total Cost per Material = Total Unit Cost × Used Quantity × Exchange Rate
Grand Total Project = Sum of all materials
```

## 📦 Modul & Fitur

### 1. Inventory Module

**Path**: `/inventory`

**Fitur**:

-   CRUD operations untuk material
-   Quick Add via modal AJAX
-   Multi-currency pricing
-   Freight cost calculation
-   Stock validation dengan locking
-   QR Code generation
-   Export to Excel
-   Detail view dengan material usage history

**Controller**: `InventoryController.php`

**Key Methods**:

```php
index()          // List dengan DataTables
create()         // Form tambah inventory
store()          // Save dengan validasi stock
quickStore()     // AJAX quick add
viewUsage()      // Material usage per project
export()         // Excel export dengan filter
```

### 2. Material Request Module

**Path**: `/material-requests`

**Fitur**:

-   Request workflow (pending/approved/delivered/canceled)
-   Bulk request creation
-   Bulk approval untuk logistic admin
-   Remaining quantity calculation
-   Permission-based actions
-   Real-time notifications
-   Export dengan dynamic filename

**Controller**: `MaterialRequestController.php`

**Status Management**:

```php
pending    // Initial state
approved   // Approved by admin
delivered  // Goods out completed
canceled   // Rejected/canceled
```

### 3. Goods Out Module

**Path**: `/goods-out`

**Fitur**:

-   Create from material request atau independent
-   User assignment
-   Project assignment (optional)
-   Stock validation dengan transaction
-   Automatic material usage recording
-   Export functionality

**Controller**: `GoodsOutController.php`

### 4. Goods In Module

**Path**: `/goods-in`

**Fitur**:

-   Return barang dari goods out
-   Independent goods in (tanpa goods out reference)
-   Stock reconciliation
-   Material usage sync
-   Project tracking

**Controller**: `GoodsInController.php`

### 5. Material Usage Module

**Path**: `/material-usage`

**Fitur**:

-   View usage per project/material
-   Automatic calculation
-   Usage rate percentage
-   Export to Excel
-   Delete (super admin only)

**Controller**: `MaterialUsageController.php`

**Helper Class**:

```php
MaterialUsageHelper::record($inventoryId, $projectId, $quantity)
MaterialUsageHelper::sync($inventoryId, $projectId)
```

### 6. Project Module

**Path**: `/projects`

**Fitur**:

-   Project CRUD
-   Department assignment
-   Status management
-   Timeline tracking (start_date, deadline, finish_date)
-   Project parts management
-   Image upload
-   Export to Excel
-   Archive functionality

**Controller**: `ProjectController.php`

### 7. Project Costing Module

**Path**: `/costing-report`

**Fitur**:

-   Cost calculation per project
-   Material breakdown
-   Multi-currency conversion to IDR
-   Freight cost inclusion
-   Grand total calculation
-   Export to Excel

**Controller**: `ProjectCostingController.php`

**Cost Formula**:

```php
Unit Cost = Price + Domestic Freight + International Freight
Total Material Cost = Unit Cost × Used Qty × Exchange Rate (to IDR)
```

### 8. Material Planning Module

**Path**: `/material-planning`

**Fitur**:

-   Planning per project
-   Multi-material planning
-   Qty estimation
-   Created date & last update tracking
-   Export functionality

**Controller**: `MaterialPlanningController.php`

### 9. Dashboard Module

**Path**: `/dashboard`

**Fitur**:

-   Low stock alerts
-   Pending material requests
-   Recent activities
-   Statistics charts
-   Quick actions

**Controller**: `DashboardController.php`

## 🔐 Role & Permissions

### User Roles

#### 1. Super Admin (`super_admin`)

**Full Access**:

-   ✅ Semua CRUD operations
-   ✅ Approve/reject material requests
-   ✅ Delete any records
-   ✅ Manage users & departments
-   ✅ Archive projects
-   ✅ Edit finish_date untuk project

**Helper Method**:

```php
$user->isSuperAdmin() // Returns boolean
```

#### 2. Admin Logistic (`admin_logistic`)

**Material Management**:

-   ✅ View all material requests
-   ✅ Approve material requests
-   ✅ Create goods out/in
-   ✅ View all inventory
-   ❌ Delete records (read-only untuk beberapa modul)

**Helper Method**:

```php
$user->isLogisticAdmin() // Returns boolean
```

#### 3. Regular User (`user`)

**Limited Access**:

-   ✅ Create material requests (own requests only)
-   ✅ View own requests
-   ✅ View inventory (read-only)
-   ❌ Approve requests
-   ❌ Delete records
-   ❌ Create goods out

**Helper Method**:

```php
$user->isRequestOwner($materialRequest) // Returns boolean
```

### Permission Checks Pattern

**In Controller**:

```php
// Check super admin
if (!Auth::user()->isSuperAdmin()) {
    abort(403, 'Unauthorized');
}

// Check logistic admin or super admin
if (!Auth::user()->isLogisticAdmin() && !Auth::user()->isSuperAdmin()) {
    return redirect()->back()->with('error', 'Permission denied');
}

// Check request owner
if (!$user->isRequestOwner($materialRequest) && !$user->isSuperAdmin()) {
    return redirect()->back()->with('error', 'You can only edit your own requests');
}
```

**In Blade Views**:

```blade
@if(auth()->user()->isSuperAdmin())
    <button class="btn btn-danger">Delete</button>
@endif

@can('update', $materialRequest)
    <a href="{{ route('material-requests.edit', $materialRequest) }}">Edit</a>
@endcan
```

## 📡 API & Broadcasting

### Real-time Events

#### Material Request Events

```php
// Event: MaterialRequestUpdated
Channel: 'material-requests'
Data: {
    id, status, user_id, inventory_id,
    project_id, qty, requested_at
}
```

**JavaScript Listener**:

```javascript
Echo.channel("material-requests").listen("MaterialRequestUpdated", (e) => {
    // Update UI
    updateDataTable(e.materialRequest);
    showToast(e.materialRequest, e.action);
    playNotificationSound();
});
```

### AJAX Endpoints

#### Quick Add Endpoints

```javascript
// Quick Add Material
POST /inventory/quick-store
Body: { name, quantity, unit, price, category_id }
Response: { success, inventory: {...} }

// Quick Add Project
POST /projects/quick-store
Body: { name, qty, department_id }
Response: { success, project: {...} }

// Quick Add Supplier
POST /suppliers/quick-store
Body: { name, contact }
Response: { success, supplier: {...} }
```

#### DataTables AJAX

```javascript
// Get inventory data
GET /inventory?ajax=1
Response: DataTables JSON format

// Get material requests
GET /material-requests?ajax=1&status=pending
Response: DataTables JSON format
```

## 📊 Export & Reporting

### Excel Export Features

Semua modul utama support Excel export dengan **dynamic filename** berdasarkan filter:

#### Material Requests Export

```php
Route: /material-requests/export
Filename Format: material_requests_{filters}_{date}.xlsx
Filters: status, project, material, requester, date
```

#### Inventory Export

```php
Route: /inventory/export
Filename Format: inventory_{filters}_{date}.xlsx
Filters: category, supplier, min_stock
```

#### Projects Export

```php
Route: /projects/export
Filename Format: projects_{filters}_{date}.xlsx
Filters: department, quantity
```

#### Material Usage Export

```php
Route: /material-usage/export
Filename Format: material_usage_{filters}_{date}.xlsx
Filters: project, material
```

#### Project Costing Export

```php
Route: /costing-report/{project}/export
Filename Format: costing_report_{project_name}_{date}.xlsx
Includes: Material breakdown, costs, totals
```

### Export Implementation Pattern

**Controller**:

```php
public function export(Request $request)
{
    $query = Model::query();

    // Apply filters
    if ($request->filter) {
        $query->where('column', $request->filter);
    }

    // Dynamic filename
    $fileName = 'export';
    if ($request->filter) {
        $fileName .= '_filter-' . $request->filter;
    }
    $fileName .= '_' . now()->format('Y-m-d') . '.xlsx';

    return Excel::download(new ModelExport($query->get()), $fileName);
}
```

**Export Class** (`app/Exports/`):

```php
class ModelExport implements FromView
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('exports.model', [
            'data' => $this->data
        ]);
    }
}
```

## 🎨 UI/UX Patterns

### Select2 Integration

**Standard Implementation**:

```javascript
$(".select2")
    .select2({
        theme: "bootstrap-5",
        allowClear: true,
        placeholder: "Select an option",
        width: "100%",
    })
    .on("select2:open", function () {
        // Auto-focus search field
        setTimeout(() => {
            document.querySelector(".select2-search__field").focus();
        }, 100);
    });
```

**Dynamic Update After AJAX**:

```javascript
// Add new option to Select2
$("#select-id").append(new Option(text, value, true, true)).trigger("change");

// Refresh all Select2 instances
$(".select2").each(function () {
    $(this).trigger("change");
});
```

### Quick Add Modal Pattern

**HTML Structure**:

```blade
<!-- Trigger Button -->
<button type="button" id="btnQuickAdd" class="btn btn-outline-primary btn-sm">
    + Quick Add
</button>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Add</h5>
            </div>
            <div class="modal-body">
                Are you sure to add via Quick Add?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmBtn" class="btn btn-primary">Yes, Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="quickAddModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="quickAddForm">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Add</h5>
                </div>
                <div class="modal-body">
                    <!-- Form fields -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

**JavaScript Implementation**:

```javascript
$(document).ready(function () {
    // Step 1: Show confirmation
    $("#btnQuickAdd").on("click", function () {
        $("#confirmModal").modal("show");
    });

    // Step 2: Show form after confirmation
    $("#confirmBtn").on("click", function () {
        $("#confirmModal").modal("hide");
        $("#quickAddModal").modal("show");
    });

    // Step 3: AJAX submission
    $("#quickAddForm").on("submit", function (e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Loading state
        submitBtn
            .html(
                '<span class="spinner-border spinner-border-sm me-2"></span>Saving...'
            )
            .prop("disabled", true);

        $.ajax({
            url: "/route/quick-store",
            method: "POST",
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    // Update Select2
                    const newOption = new Option(
                        response.data.name,
                        response.data.id,
                        true,
                        true
                    );
                    $("#target-select").append(newOption).trigger("change");

                    // Close modal and reset form
                    $("#quickAddModal").modal("hide");
                    $("#quickAddForm")[0].reset();

                    // Success message
                    Swal.fire({
                        icon: "success",
                        title: "Success!",
                        text: response.message,
                        timer: 2000,
                    });
                }
            },
            error: function (xhr) {
                // Error handling
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    let errorMessage = "";
                    $.each(errors, function (key, value) {
                        errorMessage += value[0] + "<br>";
                    });
                    Swal.fire({
                        icon: "error",
                        title: "Validation Error",
                        html: errorMessage,
                    });
                }
            },
            complete: function () {
                // Reset button
                submitBtn.html(originalText).prop("disabled", false);
            },
        });
    });
});
```

### DataTables Server-Side

**Controller Implementation**:

```php
public function index(Request $request)
{
    if ($request->ajax()) {
        $query = Model::with('relations')->select('models.*');

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('checkbox', function($item) {
                return '<input type="checkbox" class="row-checkbox" value="'.$item->id.'">';
            })
            ->addColumn('actions', function($item) {
                $actions = '';
                if (auth()->user()->can('update', $item)) {
                    $actions .= '<a href="'.route('model.edit', $item).'" class="btn btn-sm btn-warning">Edit</a> ';
                }
                if (auth()->user()->can('delete', $item)) {
                    $actions .= '<form action="'.route('model.destroy', $item).'" method="POST" class="d-inline delete-form">
                        '.csrf_field().method_field('DELETE').'
                        <button type="button" class="btn btn-sm btn-danger btn-delete">Delete</button>
                    </form>';
                }
                return $actions;
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    return view('model.index');
}
```

**View Implementation**:

```blade
<table id="dataTable" class="table table-bordered table-hover">
    <thead>
        <tr>
            <th><input type="checkbox" id="select-all"></th>
            <th>Name</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

@push('scripts')
<script>
$(document).ready(function() {
    const table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("model.index") }}',
            data: function(d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        order: [[1, 'asc']]
    });

    // Select all checkbox
    $('#select-all').on('click', function() {
        $('.row-checkbox').prop('checked', this.checked);
    });

    // Filter trigger
    $('#statusFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush
```

### Bulk Operations Pattern

**HTML**:

```blade
<button type="button" id="bulkActionBtn" class="btn btn-primary" disabled>
    Process Selected (0)
</button>
```

**JavaScript**:

```javascript
// Update button state
function updateBulkButton() {
    const checked = $(".row-checkbox:checked").length;
    $("#bulkActionBtn")
        .prop("disabled", checked === 0)
        .text(`Process Selected (${checked})`);
}

$(document).on("change", ".row-checkbox", updateBulkButton);

// Bulk action handler
$("#bulkActionBtn").on("click", function () {
    const selected = $(".row-checkbox:checked")
        .map(function () {
            return $(this).val();
        })
        .get();

    if (selected.length === 0) {
        Swal.fire("Warning", "Please select at least one item", "warning");
        return;
    }

    Swal.fire({
        title: "Confirm Bulk Action",
        text: `Process ${selected.length} selected items?`,
        icon: "question",
        showCancelButton: true,
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "/route/bulk-action",
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    ids: selected,
                },
                success: function (response) {
                    Swal.fire("Success!", response.message, "success");
                    table.ajax.reload();
                    $("#select-all").prop("checked", false);
                    updateBulkButton();
                },
            });
        }
    });
});
```

## 🔧 Development Best Practices

### Database Transactions

**Always use transactions for multi-table operations**:

```php
DB::beginTransaction();
try {
    // Lock inventory row
    $inventory = Inventory::where('id', $id)
        ->lockForUpdate()
        ->first();

    // Validate stock
    if ($request->qty > $inventory->quantity) {
        DB::rollBack();
        return back()->withErrors(['qty' => 'Insufficient stock']);
    }

    // Create records
    $goodsOut = GoodsOut::create([...]);
    $materialRequest->update(['status' => 'delivered']);
    $inventory->decrement('quantity', $request->qty);

    // Record usage
    MaterialUsageHelper::record($inventory->id, $project->id, $request->qty);

    DB::commit();

    // Trigger events after commit
    event(new MaterialRequestUpdated($materialRequest, 'delivered'));

    return redirect()->route('goods-out.index')
        ->with('success', 'Goods out created successfully!');

} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Goods Out Error: ' . $e->getMessage());
    return back()->withInput()
        ->withErrors(['error' => 'Failed to create goods out: ' . $e->getMessage()]);
}
```

### Error Handling Pattern

**Blade Views**:

```blade
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Whoops!</strong> There were some problems:
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

**AJAX Error Handling**:

```javascript
$.ajax({
    url: "/route",
    method: "POST",
    data: formData,
    success: function (response) {
        // Handle success
    },
    error: function (xhr, status, error) {
        if (xhr.status === 422) {
            // Validation errors
            const errors = xhr.responseJSON.errors;
            let errorHtml = "<ul>";
            $.each(errors, function (key, value) {
                errorHtml += "<li>" + value[0] + "</li>";
            });
            errorHtml += "</ul>";

            Swal.fire({
                icon: "error",
                title: "Validation Error",
                html: errorHtml,
            });
        } else {
            // Server error
            Swal.fire({
                icon: "error",
                title: "Error",
                text: xhr.responseJSON?.message || "An error occurred",
            });
        }
    },
});
```

### Form Validation

**Request Validation**:

```php
// app/Http/Requests/StoreInventoryRequest.php
public function rules()
{
    return [
        'name' => 'required|string|max:255|unique:inventories,name',
        'quantity' => 'required|numeric|min:0',
        'price' => 'required|numeric|min:0',
        'unit' => 'required|string|max:50',
        'category_id' => 'required|exists:categories,id',
        'supplier_id' => 'nullable|exists:suppliers,id',
        'img' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
    ];
}

public function messages()
{
    return [
        'name.unique' => 'Material name already exists',
        'quantity.min' => 'Quantity must be at least 0',
        'img.max' => 'Image size must not exceed 2MB'
    ];
}
```

**Frontend Validation**:

```javascript
function validateForm(formData) {
    const errors = [];

    if (!formData.name || formData.name.trim() === "") {
        errors.push("Name is required");
    }

    if (!formData.quantity || formData.quantity < 0) {
        errors.push("Quantity must be a positive number");
    }

    if (errors.length > 0) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            html:
                "<ul>" +
                errors.map((e) => "<li>" + e + "</li>").join("") +
                "</ul>",
        });
        return false;
    }

    return true;
}
```

## 🐛 Troubleshooting

### Common Issues

#### 1. Broadcasting Not Working

**Symptoms**: Notifikasi real-time tidak muncul

**Solutions**:

```bash
# Check .env configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=ap1

# Clear config cache
php artisan config:clear
php artisan cache:clear

# Reinstall Laravel Echo & Pusher
npm install --save-dev laravel-echo pusher-js

# Rebuild assets
npm run build
```

#### 2. DataTables Not Loading

**Symptoms**: Table shows "Loading..." atau error 500

**Solutions**:

```bash
# Check route is registered
php artisan route:list | grep "model.index"

# Enable query log to debug
// In controller
\DB::enableQueryLog();
// ... your query
dd(\DB::getQueryLog());

# Check AJAX response in browser console
// Should return JSON with "data", "recordsTotal", etc.
```

#### 3. Select2 Not Working After AJAX

**Symptoms**: Select2 tidak ter-initialize setelah AJAX load

**Solution**:

```javascript
// Re-initialize Select2 after AJAX
function initSelect2() {
    $(".select2").select2({
        theme: "bootstrap-5",
        allowClear: true,
    });
}

// Call after AJAX success
$.ajax({
    success: function (response) {
        $("#container").html(response);
        initSelect2(); // Re-initialize
    },
});
```

#### 4. Transaction Deadlock

**Symptoms**: "Deadlock found when trying to get lock"

**Solution**:

```php
// Always use consistent lock order
// Wrong:
$project = Project::lockForUpdate()->find($id);
$inventory = Inventory::lockForUpdate()->find($id);

// Correct:
$inventory = Inventory::lockForUpdate()->find($id); // Always lock inventory first
$project = Project::lockForUpdate()->find($id);

// Add retry logic
$maxRetries = 3;
$attempt = 0;

while ($attempt < $maxRetries) {
    try {
        DB::beginTransaction();
        // Your transaction code
        DB::commit();
        break;
    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack();
        if ($e->getCode() == '40001' && $attempt < $maxRetries - 1) {
            $attempt++;
            sleep(1); // Wait before retry
            continue;
        }
        throw $e;
    }
}
```

#### 5. File Upload Issues

**Symptoms**: Image upload gagal atau file tidak tersimpan

**Solutions**:

```bash
# Check storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create storage link
php artisan storage:link

# Check .env max upload
upload_max_filesize = 10M
post_max_size = 10M

# Restart web server
# For Apache
sudo service apache2 restart

# For Nginx
sudo service nginx restart
```

#### 6. Session/Auth Issues

**Symptoms**: User logout tiba-tiba atau session hilang

**Solutions**:

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Check session configuration in .env
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Check session permissions
chmod -R 775 storage/framework/sessions
```

### Debug Mode

**Enable Debug Mode** (development only):

```env
APP_DEBUG=true
```

**Laravel Debugbar**:

```bash
composer require barryvdh/laravel-debugbar --dev

# Will auto-register, shows query log, variables, etc.
```

**Query Logging**:

```php
// In AppServiceProvider boot()
if (app()->environment('local')) {
    \DB::listen(function($query) {
        \Log::info($query->sql, $query->bindings);
    });
}
```

## 📚 Additional Resources

### Documentation Links

-   [Laravel 11 Documentation](https://laravel.com/docs/11.x)
-   [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3)
-   [DataTables Documentation](https://datatables.net/)
-   [Select2 Documentation](https://select2.org/)
-   [Pusher Documentation](https://pusher.com/docs)

### Package Documentation

-   [Yajra DataTables](https://yajrabox.com/docs/laravel-datatables)
-   [Maatwebsite Excel](https://docs.laravel-excel.com/)
-   [Laravel Auditing](https://laravel-auditing.com/)
-   [Simple QR Code](https://www.simplesoftware.io/#/docs/simple-qrcode)

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

**Coding Standards**:

-   Follow PSR-12 for PHP code
-   Use meaningful variable/function names
-   Add comments for complex logic
-   Write tests for new features
-   Update documentation

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**IT DCM Team**

## 🙏 Acknowledgments

-   Laravel Framework
-   Bootstrap Team
-   All open-source contributors
-   Community support

---

**Last Updated**: October 2025  
**Version**: 2.0  
**Laravel Version**: 11.x
