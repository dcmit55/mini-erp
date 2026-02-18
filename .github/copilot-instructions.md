# Copilot Instructions - iSyment Inventory Management System

## Project Overview

This is a Laravel 11 inventory management system (`iSyment`) focused on material requests, goods tracking, and project costing. The system manages the complete lifecycle from material requests to goods in/out operations with real-time notifications.

## Core Architecture

### Key Entities & Relationships

-   **Material Requests** → **Goods Out** → **Goods In** → **Material Usage** (main workflow)
-   `Inventory` (materials) ↔ `Projects` ↔ `Departments` ↔ `Users`
-   Status-driven workflows: `pending` → `approved` → `delivered` (material requests)
-   Role-based permissions: `super_admin`, `admin_logistic`, regular users

### Critical Models

```php
// User roles with helper methods
User::isSuperAdmin() / isLogisticAdmin() / isRequestOwner()

// Material Request workflow
MaterialRequest->status: pending|approved|delivered|canceled
MaterialRequest->getRemainingQtyAttribute() // qty - processed_qty

// Inventory with cost calculations
Inventory->getTotalUnitCostAttribute() // price + freight costs
```

## Development Patterns

### Quick Add Modals Pattern

The system extensively uses "Quick Add" functionality via AJAX modals:

```javascript
// Standard pattern in create/edit views
$("#btnQuickAddMaterial").on("click", function () {
    $("#confirmAddMaterialModal").modal("show"); // Confirmation first
});

$("#quickAddMaterialForm").on("submit", function (e) {
    // AJAX submission → update Select2 options → close modal
});
```

### Status Management & Permissions

```php
// Controllers check permissions via model methods
if (!$authUser->isLogisticAdmin() && !$isRequestOwner) {
    return redirect()->with('error', 'Permission denied');
}

// Status updates trigger events
event(new MaterialRequestUpdated($materialRequest, 'status'));
```

### Bulk Operations

Bulk operations are common (material requests, goods out/in):

-   Use DataTables with checkboxes for selection
-   Validate selected items via AJAX before processing
-   Show confirmation modals with item details

### Real-time Updates

Uses Laravel Broadcasting with Pusher:

```php
// Events implement ShouldBroadcast
class MaterialRequestUpdated implements ShouldBroadcast {
    public function broadcastOn() {
        return new Channel('material-requests');
    }
}
```

## Key Conventions

### Form Validation & UX

-   Always use database transactions for multi-step operations
-   Lock inventory rows with `lockForUpdate()` for stock validation
-   Show loading spinners during AJAX operations
-   Use Bootstrap alerts with auto-dismiss for feedback

### Select2 Integration

```javascript
$(".select2")
    .select2({
        theme: "bootstrap-5",
        allowClear: true,
        placeholder: "Select an option",
    })
    .on("select2:open", function () {
        // Auto-focus search field
        setTimeout(
            () => document.querySelector(".select2-search__field").focus(),
            100
        );
    });
```

### Export Functionality

-   All major tables have Excel export via `maatwebsite/excel`
-   Dynamic filename generation based on filters
-   Export controllers accept same filters as index views

### Error Handling Pattern

```php
DB::beginTransaction();
try {
    // Operations with locked inventory
    $inventory = Inventory::where('id', $id)->lockForUpdate()->first();

    if ($request->qty > $inventory->quantity) {
        DB::rollBack();
        return back()->withErrors(['qty' => 'Insufficient stock']);
    }

    // Create records...
    DB::commit();
    event(new MaterialRequestUpdated($materialRequest, 'created'));
} catch (\Exception $e) {
    DB::rollBack();
    return back()->withInput()->withErrors(['error' => $e->getMessage()]);
}
```

## Development Workflow

### Key Commands

```bash
php artisan serve                    # Local development
npm run dev / npm run watch          # Asset compilation
php artisan migrate:fresh --seed     # Reset database
php artisan cache:clear              # Clear all caches
```

### Testing & Debugging

-   Laravel Debugbar available in development
-   Query detector for N+1 problem identification
-   Use `inventory_db_upg_larv.sql` for database setup

### Asset Management

-   Laravel Mix for CSS/JS compilation
-   Bootstrap 5 + jQuery + Select2 stack
-   Custom CSS uses gradient themes and responsive design

## Critical Integration Points

### DataTables Server-Side

Most index pages use Yajra DataTables with server-side processing:

```php
if ($request->ajax()) {
    return DataTables::of($query)
        ->addColumn('actions', function($item) {
            // Permission-based action buttons
        })
        ->rawColumns(['checkbox', 'actions'])
        ->make(true);
}
```

### Material Usage Helper

`MaterialUsageHelper::record()` automatically calculates and records material consumption when goods are used.

### QR Code Generation

Uses `simplesoftwareio/simple-qrcode` for inventory item tracking.

## Common Pitfalls to Avoid

1. **Stock Validation**: Always validate inventory quantity before creating material requests
2. **Permission Checks**: Implement role-based access in both controllers and views
3. **Transaction Safety**: Use database transactions for multi-table operations
4. **Event Broadcasting**: Trigger events after successful database commits
5. **Select2 Updates**: When adding options via AJAX, update all instances and trigger change events

## File Locations

-   Controllers: `app/Http/Controllers/`
-   Models with relationships: `app/Models/`
-   Views with shared modals: `resources/views/`
-   Events: `app/Events/` (broadcasting enabled)
-   Helper classes: `app/Helpers/`
