# SymCore - Mini ERP DCM v2

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Sistem manajemen inventori berbasis Laravel 11 yang pada saat ini fokus pada material requests, goods tracking, dan project costing. Sistem ini mengelola siklus lengkap dari permintaan material hingga operasi keluar-masuk barang dengan notifikasi real-time.

## 📋 Daftar Isi

<<<<<<< HEAD
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
=======
- [Fitur Utama](#-fitur-utama)
- [Teknologi Stack](#-teknologi-stack)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Struktur Database](#-struktur-database)
- [Alur Kerja Sistem](#-alur-kerja-sistem)
- [Modul & Fitur](#-modul--fitur)
- [Role & Permissions](#-role--permissions)
- [API & Broadcasting](#-api--broadcasting)
- [Export & Reporting](#-export--reporting)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

## 🎯 Fitur Utama

### Material Management

<<<<<<< HEAD
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
=======
- ✅ Katalog inventori dengan multi-currency support
- ✅ Tracking stock real-time dengan lock mechanism
- ✅ Material request workflow (pending → approved → delivered)
- ✅ Bulk operations untuk efisiensi
- ✅ QR Code generation untuk tracking

### Goods Operations

- ✅ Goods Out (pengeluaran barang untuk project)
- ✅ Goods In (pengembalian barang dari project)
- ✅ Material Usage tracking otomatis
- ✅ Integration dengan project costing

### Project Management

- ✅ Project tracking dengan status & timeline
- ✅ Department assignment
- ✅ Material usage per project
- ✅ Cost calculation dengan freight costs
- ✅ Project summary & reports

### Advanced Features

- ✅ Real-time notifications dengan Pusher
- ✅ Role-based access control (RBAC)
- ✅ Audit trail dengan owen-it/auditing
- ✅ Excel export untuk semua modul
- ✅ Material planning & forecasting
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

## 🛠 Teknologi Stack

### Backend

<<<<<<< HEAD
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
=======
- **Framework**: Laravel 11.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+
- **Broadcasting**: Pusher
- **Package Manager**: Composer

### Frontend

- **UI Framework**: Bootstrap 5.3
- **JavaScript**: jQuery 3.6
- **DataTables**: Server-side processing
- **Select2**: Advanced select boxes
- **SweetAlert2**: Beautiful alerts
- **Chart.js**: Data visualization
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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

<<<<<<< HEAD
-   PHP >= 8.1
-   Composer 2.x
-   Node.js >= 18.x & NPM
-   MySQL >= 8.0
-   Extension PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
=======
- PHP >= 8.1
- Composer 2.x
- Node.js >= 18.x & NPM
- MySQL >= 8.0
- Extension PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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
APP_NAME="SymCore"
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

<<<<<<< HEAD
-   `users`: User management dengan role
-   `departments`: Departemen/divisi
-   `categories`: Kategori material
-   `suppliers`: Data supplier
-   `currencies`: Multi-currency dengan exchange rate
-   `project_statuses`: Status project (active, completed, dll)
-   `project_parts`: Part/komponen project
-   `audits`: Audit trail (owen-it/auditing)
=======
- `users`: User management dengan role
- `departments`: Departemen/divisi
- `categories`: Kategori material
- `suppliers`: Data supplier
- `currencies`: Multi-currency dengan exchange rate
- `project_statuses`: Status project (active, completed, dll)
- `project_parts`: Part/komponen project
- `audits`: Audit trail (owen-it/auditing)
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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

<<<<<<< HEAD
-   CRUD operations untuk material
-   Quick Add via modal AJAX
-   Multi-currency pricing
-   Freight cost calculation
-   Stock validation dengan locking
-   QR Code generation
-   Export to Excel
-   Detail view dengan material usage history
=======
- CRUD operations untuk material
- Quick Add via modal AJAX
- Multi-currency pricing
- Freight cost calculation
- Stock validation dengan locking
- QR Code generation
- Export to Excel
- Detail view dengan material usage history
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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

<<<<<<< HEAD
-   Request workflow (pending/approved/delivered/canceled)
-   Bulk request creation
-   Bulk approval untuk logistic admin
-   Remaining quantity calculation
-   Permission-based actions
-   Real-time notifications
-   Export dengan dynamic filename
=======
- Request workflow (pending/approved/delivered/canceled)
- Bulk request creation
- Bulk approval untuk logistic admin
- Remaining quantity calculation
- Permission-based actions
- Real-time notifications
- Export dengan dynamic filename
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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

<<<<<<< HEAD
-   Create from material request atau independent
-   User assignment
-   Project assignment (optional)
-   Stock validation dengan transaction
-   Automatic material usage recording
-   Export functionality
=======
- Create from material request atau independent
- User assignment
- Project assignment (optional)
- Stock validation dengan transaction
- Automatic material usage recording
- Export functionality
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `GoodsOutController.php`

### 4. Goods In Module

**Path**: `/goods-in`

**Fitur**:

<<<<<<< HEAD
-   Return barang dari goods out
-   Independent goods in (tanpa goods out reference)
-   Stock reconciliation
-   Material usage sync
-   Project tracking
=======
- Return barang dari goods out
- Independent goods in (tanpa goods out reference)
- Stock reconciliation
- Material usage sync
- Project tracking
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `GoodsInController.php`

### 5. Material Usage Module

**Path**: `/material-usage`

**Fitur**:

<<<<<<< HEAD
-   View usage per project/material
-   Automatic calculation
-   Usage rate percentage
-   Export to Excel
-   Delete (super admin only)
=======
- View usage per project/material
- Automatic calculation
- Usage rate percentage
- Export to Excel
- Delete (super admin only)
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `MaterialUsageController.php`

**Helper Class**:

```php
MaterialUsageHelper::record($inventoryId, $projectId, $quantity)
MaterialUsageHelper::sync($inventoryId, $projectId)
```

### 6. Project Module

**Path**: `/projects`

**Fitur**:

<<<<<<< HEAD
-   Project CRUD
-   Department assignment
-   Status management
-   Timeline tracking (start_date, deadline, finish_date)
-   Project parts management
-   Image upload
-   Export to Excel
-   Archive functionality
=======
- Project CRUD
- Department assignment
- Status management
- Timeline tracking (start_date, deadline, finish_date)
- Project parts management
- Image upload
- Export to Excel
- Archive functionality
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `ProjectController.php`

### 7. Project Costing Module

**Path**: `/costing-report`

**Fitur**:

<<<<<<< HEAD
-   Cost calculation per project
-   Material breakdown
-   Multi-currency conversion to IDR
-   Freight cost inclusion
-   Grand total calculation
-   Export to Excel
=======
- Cost calculation per project
- Material breakdown
- Multi-currency conversion to IDR
- Freight cost inclusion
- Grand total calculation
- Export to Excel
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `ProjectCostingController.php`

**Cost Formula**:

```php
Unit Cost = Price + Domestic Freight + International Freight
Total Material Cost = Unit Cost × Used Qty × Exchange Rate (to IDR)
```

### 8. Material Planning Module

**Path**: `/material-planning`

**Fitur**:

<<<<<<< HEAD
-   Planning per project
-   Multi-material planning
-   Qty estimation
-   Created date & last update tracking
-   Export functionality
=======
- Planning per project
- Multi-material planning
- Qty estimation
- Created date & last update tracking
- Export functionality
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `MaterialPlanningController.php`

### 9. Dashboard Module

**Path**: `/dashboard`

**Fitur**:

<<<<<<< HEAD
-   Low stock alerts
-   Pending material requests
-   Recent activities
-   Statistics charts
-   Quick actions
=======
- Low stock alerts
- Pending material requests
- Recent activities
- Statistics charts
- Quick actions
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Controller**: `DashboardController.php`

## 🔐 Role & Permissions

### User Roles

#### 1. Super Admin (`super_admin`)

**Full Access**:

<<<<<<< HEAD
-   ✅ Semua CRUD operations
-   ✅ Approve/reject material requests
-   ✅ Delete any records
-   ✅ Manage users & departments
-   ✅ Archive projects
-   ✅ Edit finish_date untuk project
=======
- ✅ Semua CRUD operations
- ✅ Approve/reject material requests
- ✅ Delete any records
- ✅ Manage users & departments
- ✅ Archive projects
- ✅ Edit finish_date untuk project
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Helper Method**:

```php
$user->isSuperAdmin() // Returns boolean
```

#### 2. Admin Logistic (`admin_logistic`)

**Material Management**:

<<<<<<< HEAD
-   ✅ View all material requests
-   ✅ Approve material requests
-   ✅ Create goods out/in
-   ✅ View all inventory
-   ❌ Delete records (read-only untuk beberapa modul)
=======
- ✅ View all material requests
- ✅ Approve material requests
- ✅ Create goods out/in
- ✅ View all inventory
- ❌ Delete records (read-only untuk beberapa modul)
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

**Helper Method**:

```php
$user->isLogisticAdmin() // Returns boolean
```

#### 3. Regular User (`user`)

**Limited Access**:

<<<<<<< HEAD
-   ✅ Create material requests (own requests only)
-   ✅ View own requests
-   ✅ View inventory (read-only)
-   ❌ Approve requests
-   ❌ Delete records
-   ❌ Create goods out
=======
- ✅ Create material requests (own requests only)
- ✅ View own requests
- ✅ View inventory (read-only)
- ❌ Approve requests
- ❌ Delete records
- ❌ Create goods out
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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
<<<<<<< HEAD
$("#select-id").append(new Option(text, value, true, true)).trigger("change");
=======
$("#select-id")
    .append(new Option(text, value, true, true))
    .trigger("change");
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

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
<<<<<<< HEAD
                '<span class="spinner-border spinner-border-sm me-2"></span>Saving...'
=======
                '<span class="spinner-border spinner-border-sm me-2"></span>Saving...',
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40
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
<<<<<<< HEAD
                        true
=======
                        true,
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40
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

## 🔐 API Token Authentication

Sistem menggunakan **Static API Token** untuk komunikasi server-to-server yang ringan dan stateless. Token bersifat permanen tanpa expiration untuk konsumsi API oleh aplikasi eksternal (BotTime).

### Mengapa Static Token?

**Tidak Menggunakan Laravel Sanctum** karena:

<<<<<<< HEAD
-   ❌ Terlalu _heavyweight_ untuk server-to-server
-   ❌ Memerlukan user session & token rotation
-   ❌ Overhead yang tidak diperlukan untuk internal API

**Static Token Benefits**:

-   ✅ **Ringan**: Hanya 1 query database per request
-   ✅ **Stateless**: Tanpa session/cookie
-   ✅ **Permanen**: Token tidak expired
-   ✅ **Sederhana**: Setup minimal, mudah maintenance
=======
## 🛡️ Data Governance: Lark Integration

Sistem ini menerapkan **data governance ketat** untuk memastikan integritas data project yang berasal dari Lark sebagai **single source of truth**.

### Aturan Bisnis

#### Project Source Classification

| Source Type          | `created_by` Value                | Status       | Can Be Used in Business Process?          | Visibility          |
| -------------------- | --------------------------------- | ------------ | ----------------------------------------- | ------------------- |
| **Lark (VALID)**     | `'Sync from Lark'`                | ✅ Active    | ✅ YES - Boleh dipilih untuk semua proses | Full access         |
| **Legacy (INVALID)** | `!= 'Sync from Lark'` atau `NULL` | 🔒 Read-only | ❌ NO - Hanya untuk historical reporting  | View only, disabled |

#### Business Rules

1. **ONLY Lark projects** dapat digunakan untuk:
    - Material Requests (create/edit)
    - Goods Out/In operations
    - Material Planning
    - Purchase Requests
    - Timing records
    - Semua proses bisnis aktif

2. **Legacy projects**:
    - Tetap bisa **dilihat** (untuk audit trail & reporting)
    - **TIDAK bisa dipilih** di form apa pun
    - Muncul dengan badge "🔒 Legacy - Cannot be used"
    - Disabled dan berwarna abu-abu di UI
    - Ditolak di backend jika dipaksa via manipulasi request

3. **Enforcement Level**:
    - ✅ **Model Level**: Scope & helper methods
    - ✅ **Validation Level**: Custom validation rule
    - ✅ **Controller Level**: Query filter & validation
    - ✅ **View Level**: Disabled options dengan styling

### Implementasi Teknis

#### 1. Model Scopes & Helper Methods

```php
// app/Models/Production/Project.php

// SCOPES
Project::fromLark()->get();  // Hanya project valid dari Lark
Project::legacy()->get();    // Hanya project legacy (historical)

// HELPER METHODS
$project->isFromLark();  // bool: Apakah project dari Lark?
$project->isLegacy();    // bool: Apakah project legacy?
$project->canBeUsed();   // bool: Boleh digunakan di proses bisnis?
```

**Implementasi Model**:

```php
// Scope untuk project VALID dari Lark
public function scopeFromLark($query)
{
    return $query->where('created_by', 'Sync from Lark');
}

// Scope untuk project LEGACY
public function scopeLegacy($query)
{
    return $query->where('created_by', '!=', 'Sync from Lark')
                 ->orWhereNull('created_by');
}

// Check apakah project dari Lark (VALID)
public function isFromLark(): bool
{
    return $this->created_by === 'Sync from Lark';
}

// Check apakah project legacy (TIDAK VALID)
public function isLegacy(): bool
{
    return !$this->isFromLark();
}

// Check apakah boleh digunakan dalam proses bisnis
public function canBeUsed(): bool
{
    return $this->isFromLark();
}
```

#### 2. Custom Validation Rule

```php
// app/Rules/ValidProjectSource.php

use App\Rules\ValidProjectSource;

// Penggunaan di Controller:
$request->validate([
    'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
]);
```

**Rule Implementation**:

```php
public function validate(string $attribute, mixed $value, Closure $fail): void
{
    if (empty($value)) return;

    $project = Project::find($value);

    if (!$project) {
        $fail('The selected project does not exist.');
        return;
    }

    // ENFORCEMENT: Tolak project legacy
    if (!$project->isFromLark()) {
        $fail('The selected project is a legacy project and cannot be used. Please select a project synced from Lark.');
        return;
    }

    // Additional: Check soft delete
    if ($project->trashed()) {
        $fail('The selected project has been deleted and cannot be used.');
        return;
    }
}
```

#### 3. Controller Query Pattern

**BEFORE (Vulnerable - accepts legacy)**:

```php
// ❌ BAHAYA: Semua project termasuk legacy
$projects = Project::orderBy('name')->get();
```

**AFTER (Secure - only Lark projects)**:

```php
// ✅ AMAN: Hanya project dari Lark
$projects = Project::fromLark()
    ->with('departments', 'status')
    ->notArchived()
    ->orderBy('name')
    ->get();
```

**Contoh Implementasi di Controller**:

```php
// MaterialRequestController.php
use App\Rules\ValidProjectSource;

public function create(Request $request)
{
    // Hanya ambil project dari Lark untuk dropdown
    $projects = Project::fromLark()
        ->with('departments', 'status')
        ->notArchived()
        ->orderBy('name')
        ->get();

    return view('logistic.material_requests.create', compact('projects'));
}

public function store(Request $request)
{
    $request->validate([
        'inventory_id' => 'required|exists:inventories,id',
        'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
        'qty' => 'required|numeric|min:0.01',
    ]);

    // Backend enforcement akan menolak jika project legacy dipaksa
    // ...
}
```

#### 4. Blade View Pattern

**Component-Based (Recommended)**:

```blade
{{-- Gunakan component untuk konsistensi --}}
<select name="project_id" class="form-select select2" required>
    <option value="">Select Project</option>
    @include('components.project-options', [
        'projects' => $projects,
        'selected' => old('project_id', $record->project_id ?? null),
        'showLegacy' => false  // false = hide legacy (untuk form create/edit)
    ])
</select>
```

**Manual Rendering (dengan Legacy Display)**:

```blade
<select name="project_id" class="form-select select2">
    <option value="">Select Project</option>
    @foreach ($projects as $project)
        @if($project->isFromLark())
            {{-- Project VALID: Normal selectable --}}
            <option value="{{ $project->id }}"
                    data-department="{{ $project->departments->pluck('name')->implode(', ') }}">
                {{ $project->name }}
            </option>
        @else
            {{-- Project LEGACY: Disabled dengan visual berbeda --}}
            <option value="{{ $project->id }}"
                    disabled
                    class="text-muted"
                    style="background-color: #f8f9fa; color: #6c757d !important;">
                🔒 {{ $project->name }} (Legacy - Cannot be used)
            </option>
        @endif
    @endforeach
</select>
```

**CSS Styling**:

```css
/* Styling untuk project legacy */
option[data-source="legacy"] {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    font-style: italic;
}

option[data-source="lark"] {
    background-color: white;
    color: #212529;
}
```

### Use Cases & Examples

#### ✅ ALLOWED: Proses Bisnis dengan Lark Project

```php
// Material Request: BERHASIL
MaterialRequest::create([
    'project_id' => 1234,  // Project dengan created_by = 'Sync from Lark'
    'inventory_id' => 56,
    'qty' => 10,
]);
// ✅ SUCCESS: Validasi lolos, data tersimpan

// Goods Out: BERHASIL
GoodsOut::create([
    'project_id' => 1234,  // Lark project
    'inventory_id' => 56,
    'quantity' => 5,
]);
// ✅ SUCCESS
```

#### ❌ REJECTED: Proses Bisnis dengan Legacy Project

```php
// Material Request: DITOLAK
$request->validate([
    'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
]);
// Input: project_id = 999 (legacy project dengan created_by = 'manual')

// ❌ VALIDATION ERROR:
// "The selected project is a legacy project and cannot be used.
//  Please select a project synced from Lark."
```

```javascript
// AJAX Manipulation Attempt: DITOLAK
$.ajax({
    url: "/material-requests/store",
    data: {
        project_id: 999, // Legacy project dipaksa via JS
        inventory_id: 56,
        qty: 10,
    },
});

// Backend Response:
// HTTP 422 Unprocessable Entity
// {
//     "errors": {
//         "project_id": [
//             "The selected project is a legacy project and cannot be used."
//         ]
//     }
// }
```

#### ✅ ALLOWED: Historical Reporting dengan Legacy Project

```php
// Reporting Query: DIIZINKAN untuk view historical data
$materialRequests = MaterialRequest::with('project')
    ->whereHas('project', function($q) {
        $q->legacy();  // Bisa query legacy untuk reporting
    })
    ->get();

// Export: DIIZINKAN
$allProjects = Project::withTrashed()->get();  // Termasuk legacy
Excel::download(new ProjectExport($allProjects), 'all_projects.xlsx');
```

### Migration & Transition Strategy

#### Jika Ada Data Legacy yang Masih Aktif

1. **Identifikasi Legacy Projects**:

    ```sql
    -- Find all legacy projects yang masih digunakan
    SELECT p.id, p.name, p.created_by, COUNT(mr.id) as active_requests
    FROM projects p
    LEFT JOIN material_requests mr ON p.id = mr.project_id
    WHERE (p.created_by != 'Sync from Lark' OR p.created_by IS NULL)
      AND p.deleted_at IS NULL
    GROUP BY p.id
    HAVING active_requests > 0;
    ```

2. **Sync Manual Projects ke Lark**:
    - Export project legacy ke Excel
    - Import ke Lark Base
    - Re-sync dari Lark
    - Sistem otomatis mark sebagai `created_by = 'Sync from Lark'`

3. **Archive Legacy Projects**:
    ```php
    // Soft delete legacy projects yang sudah tidak aktif
    Project::legacy()
        ->whereDoesntHave('materialRequests', function($q) {
            $q->where('status', '!=', 'canceled');
        })
        ->delete();
    ```

### Security Benefits

| Threat                                                   | Protection                                | Enforcement Level     |
| -------------------------------------------------------- | ----------------------------------------- | --------------------- |
| Frontend manipulation (change option value via DevTools) | ✅ Backend validation rejects             | **Controller + Rule** |
| API call with legacy project_id                          | ✅ ValidProjectSource rule fails          | **Validation Layer**  |
| Direct DB insert bypass Laravel                          | ✅ Model events + foreign key constraints | **Database Level**    |
| SQL injection dengan legacy ID                           | ✅ Eloquent ORM + prepared statements     | **ORM Level**         |
| Accidental selection in old forms                        | ✅ Query scope `fromLark()` di create()   | **Controller Query**  |

### Monitoring & Logging

```php
// Log project source violations
if (!$project->isFromLark()) {
    \Log::warning('Attempted to use legacy project in business process', [
        'project_id' => $project->id,
        'project_name' => $project->name,
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
        'endpoint' => request()->path(),
    ]);
}
```

### Testing

```php
// tests/Feature/ProjectGovernanceTest.php

public function test_legacy_project_cannot_be_used_in_material_request()
{
    $legacyProject = Project::factory()->create(['created_by' => 'manual']);

    $response = $this->post('/material-requests', [
        'project_id' => $legacyProject->id,
        'inventory_id' => 1,
        'qty' => 10,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('project_id');
}

public function test_lark_project_can_be_used_in_material_request()
{
    $larkProject = Project::factory()->create(['created_by' => 'Sync from Lark']);

    $response = $this->post('/material-requests', [
        'project_id' => $larkProject->id,
        'inventory_id' => 1,
        'qty' => 10,
    ]);

    $response->assertStatus(302);  // Redirect after success
}
```

### Best Practices

1. **Selalu Gunakan Scope di Controller**:

    ```php
    // ✅ BENAR
    $projects = Project::fromLark()->get();

    // ❌ SALAH
    $projects = Project::all();
    ```

2. **Tambahkan ValidProjectSource di Semua Form**:

    ```php
    'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
    ```

3. **Gunakan Component untuk Konsistensi**:

    ```blade
    @include('components.project-options', ['projects' => $projects])
    ```

4. **Monitor Legacy Usage**:
    - Setup alert jika ada attempt menggunakan legacy project
    - Log semua validation failures
    - Review quarterly untuk cleanup

### FAQ

**Q: Bagaimana jika user butuh edit data lama dengan legacy project?**
A: Legacy project tetap bisa **dilihat** di detail view & reports, tapi **tidak bisa diubah**. Jika perlu update, project harus di-sync ke Lark terlebih dahulu.

**Q: Apakah validation rule `ValidProjectSource` wajib di semua controller?**
A: Ya, untuk semua endpoint yang menerima `project_id` dalam proses bisnis aktif (create/update).

**Q: Bagaimana menampilkan legacy project di reporting?**
A: Gunakan query tanpa scope `fromLark()`:

```php
$allProjects = Project::all();  // Include legacy
```

Tapi tetap tampilkan badge/indicator di UI.

**Q: Apakah bisa force-enable legacy project untuk emergency?**
A: Secara teknis bisa dengan temporary disable validation, tapi **TIDAK DISARANKAN**. Lebih baik sync project ke Lark dahulu (5 menit) daripada compromise data governance.

### Summary

- ✅ **Model**: Scope `fromLark()` dan helper `canBeUsed()`
- ✅ **Validation**: Custom rule `ValidProjectSource`
- ✅ **Controller**: Query filter + validation enforcement
- ✅ **View**: Component dengan disabled legacy + styling
- ✅ **Security**: Multi-layer enforcement (Model → Validation → Controller → View)
- ✅ **User Experience**: Clear visual indicator untuk legacy projects
- ✅ **Audit**: Logging untuk compliance
- ✅ **Future-proof**: Scalable pattern untuk table lain (departments, materials, dll)

**Kesimpulan**: Implementasi ini memastikan bahwa **HANYA data dari Lark yang dapat digunakan** dalam proses bisnis, dengan enforcement di semua layer aplikasi untuk mencegah data corruption dan memastikan single source of truth.

---

## 🔐 API Token Authentication

Sistem menggunakan **Static API Token** untuk komunikasi server-to-server yang ringan dan stateless. Token bersifat permanen tanpa expiration untuk konsumsi API oleh aplikasi eksternal (BotTime).

### Mengapa Static Token?

**Tidak Menggunakan Laravel Sanctum** karena:

- ❌ Terlalu _heavyweight_ untuk server-to-server
- ❌ Memerlukan user session & token rotation
- ❌ Overhead yang tidak diperlukan untuk internal API

**Static Token Benefits**:

- ✅ **Ringan**: Hanya 1 query database per request
- ✅ **Stateless**: Tanpa session/cookie
- ✅ **Permanen**: Token tidak expired
- ✅ **Sederhana**: Setup minimal, mudah maintenance
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

### Generate Token

#### First Time Setup

```bash
# Via Artisan Command (Production)
php artisan api:token:generate "BotTime Application"

# Dengan IP Whitelist (Optional)
php artisan api:token:generate "BotTime App" --ip=192.168.1.100
```

**Output**:

```
✅ API Token Created Successfully!

+------------+------------------------------------------------------------------+
| ID         | 1                                                                |
| Name       | BotTime Application                                              |
| Token      | a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2...        |
| Status     |  Active                                                        |
+------------+------------------------------------------------------------------+

⚠️  PENTING: Simpan token ini sekarang!
```

### Token Management

```bash
# List semua tokens
php artisan api:token:list

# Nonaktifkan token
php artisan api:token:revoke 1

# Aktifkan kembali
php artisan api:token:activate 1

# Menampilkan Token Via Id
php artisan api:token:show (ID Token)
```

### Available API Endpoints

| Method | Endpoint                | Description    | Auth Required |
| ------ | ----------------------- | -------------- | ------------- |
| GET    | `/api/health`           | Health check   | ❌ No         |
| GET    | `/api/v1/projects`      | List projects  | ✅ Yes        |
| GET    | `/api/v1/projects/{id}` | Project detail | ✅ Yes        |
| GET    | `/api/v1/employees`     | List employees | ✅ Yes        |
| GET    | `/api/v1/parts`         | List parts     | ✅ Yes        |

### Request Format

**Required Headers**:

```http
X-API-TOKEN: your_token_here
Accept: application/json
```

**cURL Example**:

```bash
curl -X GET https://symcore.mascot.id/api/v1/projects \
  -H "X-API-TOKEN: a7b8c9d0e1f2g3h4..." \
  -H "Accept: application/json"
```

### Integration (BotTime PHP)

**Config Setup**:

```php
// config.php
define('SYMCORE_API_URL', 'https://symcore.mascot.id/api/v1');
define('SYMCORE_API_TOKEN', 'a7b8c9d0e1f2...'); // From generate command
```

**Helper Function**:

```php
// api_helper.php
function callSymcoreAPI($endpoint) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => SYMCORE_API_URL . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-TOKEN: ' . SYMCORE_API_TOKEN,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("API Error: HTTP $httpCode");
        return null;
    }

    return json_decode($response, true);
}
```

**Usage Example**:

```php
// Get projects untuk dropdown
$response = callSymcoreAPI('/projects');
if ($response && $response['success']) {
    $projects = $response['data'];
}

// Get employees
$employees = callSymcoreAPI('/employees');

// Get project detail
$projectDetail = callSymcoreAPI('/projects/1');
```

### Response Format

**Success (200)**:

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Project Alpha",
            "qty": 100,
            "department": {
                "id": 1,
                "name": "Production"
            }
        }
    ],
    "message": "Data retrieved successfully"
}
```

**Errors**:

```json
// 401 - No Token
{
    "success": false,
    "message": "API token is required. Please provide X-API-TOKEN header."
}

// 401 - Invalid Token
{
    "success": false,
    "message": "Invalid or inactive API token."
}

// 403 - IP Blocked
{
    "success": false,
    "message": "Your IP address is not allowed."
}
```

### Security Best Practices

#### ✅ DO:

<<<<<<< HEAD
-   Simpan token di `.env` atau config file
-   Gunakan HTTPS only (production)
-   Tambahkan IP whitelist untuk keamanan ekstra
-   Monitor `last_used_at` untuk aktivitas mencurigakan

#### ❌ DON'T:

-   Commit token ke Git repository
-   Kirim token via URL query string
-   Share token via email/chat plaintext
-   Simpan token di frontend JavaScript
=======
- Simpan token di `.env` atau config file
- Gunakan HTTPS only (production)
- Tambahkan IP whitelist untuk keamanan ekstra
- Monitor `last_used_at` untuk aktivitas mencurigakan

#### ❌ DON'T:

- Commit token ke Git repository
- Kirim token via URL query string
- Share token via email/chat plaintext
- Simpan token di frontend JavaScript
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

### Troubleshooting

#### ❌ Error: "API token is required"

**Penyebab**: Header tidak dikirim atau format salah

**Solusi**:

```php
// ✅ BENAR
'X-API-TOKEN: your_token_here'

// ❌ SALAH
'Authorization: Bearer your_token_here'  // Ini untuk Sanctum/OAuth!
```

#### ❌ Error: "Invalid or inactive API token"

**Penyebab**: Token salah atau sudah di-revoke

**Solusi**:

```bash
# Check token status
php artisan api:token:list

# Activate jika inactive
php artisan api:token:activate 1

# Generate token baru jika hilang
php artisan api:token:generate "New Token"
```

#### ❌ Error: "Your IP address is not allowed"

**Penyebab**: IP whitelist aktif dan IP Anda tidak terdaftar

**Solusi**:

```bash
# Check token configuration
php artisan api:token:list

# Buat token baru tanpa IP restriction
php artisan api:token:generate "BotTime App"

# Atau tambahkan IP ke existing token (via database)
UPDATE api_tokens SET allowed_ips = '192.168.1.100,10.0.0.50' WHERE id = 1;
```

#### ❌ CORS Issues (jika dari browser)

**Penyebab**: API dipanggil dari domain berbeda via JavaScript

**Solusi**:

```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['https://bottime.yourdomain.com'],
'allowed_headers' => ['X-API-TOKEN', 'Content-Type', 'Accept'],
```

#### ❌ Slow Response / Timeout

**Penyebab**: Query database lambat atau network issue

**Solusi**:

```php
// Increase timeout di BotTime
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 detik

// Check Laravel query performance
php artisan api:token:list  // Cek last_used_at

// Enable query log untuk debug
DB::enableQueryLog();
// ... your query
dd(DB::getQueryLog());
```

### Monitoring Token Usage

```bash
# Via artisan
php artisan api:token:list

# Via database query
SELECT id, name, is_active, last_used_at
FROM api_tokens
ORDER BY last_used_at DESC;
```

### Rate Limiting (Optional)

Untuk mencegah abuse, tambahkan rate limiting:

```php
// routes/api.php
Route::prefix('v1')->middleware(['api.token', 'throttle:60,1'])->group(function () {
    // 60 requests per minute max
    Route::get('/projects', [ProjectApiController::class, 'getProjects']);
    Route::get('/employees', [ProjectApiController::class, 'getEmployees']);
});
```

**Response saat limit terlampaui (429)**:

```json
{
    "message": "Too Many Attempts.",
    "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
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

<<<<<<< HEAD
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
=======
- [Laravel 11 Documentation](https://laravel.com/docs/11.x)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3)
- [DataTables Documentation](https://datatables.net/)
- [Select2 Documentation](https://select2.org/)
- [Pusher Documentation](https://pusher.com/docs)

### Package Documentation

- [Yajra DataTables](https://yajrabox.com/docs/laravel-datatables)
- [Maatwebsite Excel](https://docs.laravel-excel.com/)
- [Laravel Auditing](https://laravel-auditing.com/)
- [Simple QR Code](https://www.simplesoftware.io/#/docs/simple-qrcode)
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

**Coding Standards**:

<<<<<<< HEAD
-   Follow PSR-12 for PHP code
-   Use meaningful variable/function names
-   Add comments for complex logic
-   Write tests for new features
-   Update documentation
=======
- Follow PSR-12 for PHP code
- Use meaningful variable/function names
- Add comments for complex logic
- Write tests for new features
- Update documentation
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**IT DCM Team**

## 🙏 Acknowledgments

<<<<<<< HEAD
-   Laravel Framework
-   Bootstrap Team
-   All open-source contributors
-   Community support
=======
- Laravel Framework
- Bootstrap Team
- All open-source contributors
- Community support
>>>>>>> 7d24dfd0cab8ae87f6f6d3365cd893deab81ab40

---

**Initial Release Date**: July 2025
**Version**: 2.0  
**Laravel Version**: 11.x


## ��� Changelog - Latest Updates

### [2.3.0] - 2026-02-05 - Job Order Tracking Enhancement

#### ✨ Added

**Database Schema**
- ✅ `job_order_id` column in `material_usages` table (nullable, indexed, FK to job_orders)
- ✅ `job_order_id` column in `goods_out` table (nullable, indexed, FK to job_orders)
- ✅ `job_order_id` column in `goods_in` table (nullable, indexed, FK to job_orders)
- ✅ Data migration synced 4,878 material_usages records with job_order_id

**Project Costing Report**
- ✅ Search field for project names (auto-submit with 500ms debounce)
- ✅ Pagination (10 items per page)
- ✅ Auto-filter on select change (removed manual filter button)
- ✅ Hierarchical dropdown: Projects → Job Orders → Materials
- ✅ AJAX loading for materials (lazy loading)
- ✅ Loading spinner during data fetch

**Controllers**
- ✅ Updated 12 MaterialUsageHelper::sync() calls across GoodsOutController (6) and GoodsInController (6)
- ✅ Added getJobOrderMaterials() method to ProjectCostingController for AJAX

#### ��� Fixed

- ✅ Dropdown not showing in project costing (removed DataTables - using plain jQuery now)
- ✅ Detail rows disappearing (DataTables DOM manipulation issue)
- ✅ SQL error "Column job_order_id not found" in goods_out/goods_in
- ✅ Event handlers not working after table redraw
- ✅ Missing jobOrders() relationship in Project model

#### ��� Changed

- ✅ Removed DataTables library from project costing (following shipping management pattern)
- ✅ MaterialUsageHelper::sync() now accepts job_order_id parameter
- ✅ Filter behavior changed to auto-submit (better UX)

**Migration Commands:**
```bash
php artisan migrate
php artisan cache:clear && php artisan view:clear
```

---

��� **Full documentation**: See sections above for detailed technical documentation.

