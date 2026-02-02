# Data Governance Quick Implementation Guide

## 🎯 Untuk Developer: Cara Apply Data Governance ke Controller Baru

Jika Anda membuat controller atau form baru yang menggunakan **project selection**, ikuti checklist ini:

### ✅ Checklist 3 Langkah

#### 1️⃣ Import ValidProjectSource Rule

```php
// Di bagian atas controller
use App\Rules\ValidProjectSource;
```

#### 2️⃣ Filter Query dengan Scope `fromLark()`

```php
// Di method create() atau index() yang pass $projects ke view
public function create()
{
    // ✅ BENAR: Filter di backend, hanya ambil project Lark
    // Legacy projects TIDAK ditampilkan sama sekali di dropdown
    $projects = Project::fromLark()
        ->with('departments', 'status')
        ->notArchived()
        ->orderBy('name')
        ->get();

    return view('your.view', compact('projects'));
}
```

**Prinsip**: Filter di **backend** (aman), bukan di frontend (bisa di-bypass).

#### 3️⃣ Tambahkan Validation Rule di store() dan update()

```php
public function store(Request $request)
{
    $request->validate([
        'project_id' => [
            'required',
            'exists:projects,id',
            new ValidProjectSource  // ← Add this!
        ],
        // ... field lain
    ]);

    // ... logic create
}

public function update(Request $request, $id)
{
    $request->validate([
        'project_id' => [
            'nullable',  // atau 'required' sesuai business logic
            'exists:projects,id',
            new ValidProjectSource  // ← Add this!
        ],
        // ... field lain
    ]);

    // ... logic update
}
```

---

## 📋 Template Code Snippets

### Controller Template

```php
<?php

namespace App\Http\Controllers\YourModule;

use App\Http\Controllers\Controller;
use App\Models\Production\Project;
use App\Rules\ValidProjectSource;
use Illuminate\Http\Request;

class YourController extends Controller
{
    /**
     * Show form
     */
    public function create()
    {
        // DATA GOVERNANCE: Hanya project dari Lark
        $projects = Project::fromLark()
            ->with('departments', 'status')
            ->notArchived()
            ->orderBy('name')
            ->get();

        return view('your.module.create', compact('projects'));
    }

    /**
     * Store data
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
            'other_field' => 'required|string',
            // ... validation rules lainnya
        ]);

        // Your business logic here
        // YourModel::create($validated);

        return redirect()->route('your.route')->with('success', 'Data created!');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $record = YourModel::findOrFail($id);

        // DATA GOVERNANCE: Hanya project dari Lark
        $projects = Project::fromLark()
            ->with('departments', 'status')
            ->notArchived()
            ->orderBy('name')
            ->get();

        return view('your.module.edit', compact('record', 'projects'));
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $record = YourModel::findOrFail($id);

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
            'other_field' => 'required|string',
        ]);

        // $record->update($validated);

        return redirect()->route('your.route')->with('success', 'Data updated!');
    }
}
```

### Blade Template (Create/Edit Form)

```blade
{{-- your/module/create.blade.php --}}
<div class="mb-3">
    <label for="project_id" class="form-label">
        Project <span class="text-danger">*</span>
    </label>
    <select name="project_id" id="project_id" class="form-select select2" required>
        <option value="">Select Project</option>
        @foreach ($projects as $project)
            <option value="{{ $project->id }}"
                    data-department="{{ $project->departments->pluck('name')->implode(', ') }}"
                    {{ old('project_id') == $project->id ? 'selected' : '' }}>
                {{ $project->name }}
            </option>
        @endforeach
    </select>
    @error('project_id')
        <small class="text-danger">{{ $message }}</small>
    @enderror
    <div class="form-text">
        <i class="bi bi-info-circle"></i> Only projects synced from Lark can be selected
    </div>
</div>
```

### Blade Template (Dengan Component - Recommended)

```blade
{{-- Lebih clean dan consistent --}}
<div class="mb-3">
    <label for="project_id" class="form-label">
        Project <span class="text-danger">*</span>
    </label>
    <select name="project_id" id="project_id" class="form-select select2" required>
        <option value="">Select Project</option>
        @include('components.project-options', [
            'projects' => $projects,
            'selected' => old('project_id', $record->project_id ?? null)
        ])
    </select>
    @error('project_id')
        <small class="text-danger">{{ $message }}</small>
    @enderror
</div>
```

---

## 🔍 Verifikasi Implementation

### Test Checklist

Setelah implement, test hal berikut:

- [ ] **UI Test**: Buka form, pastikan hanya muncul Lark projects di dropdown
- [ ] **Validation Test**: Submit form dengan project valid → Success
- [ ] **Security Test**: Manipulasi request dengan legacy project_id via DevTools → Ditolak dengan error message
- [ ] **Error Message**: Error message jelas: "The selected project is a legacy project and cannot be used"
- [ ] **Edge Case**: Test dengan `project_id = null` → Error "required" (bukan ValidProjectSource error)

### Manual Testing Steps

```bash
# 1. Check available projects in database
php artisan tinker
>>> Project::fromLark()->pluck('name', 'id')->toArray()
>>> Project::legacy()->pluck('name', 'id')->toArray()
>>> exit

# 2. Test form submission via cURL (replace values)
curl -X POST http://your-app.test/your-endpoint \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-CSRF-TOKEN: your-token" \
  -d "project_id=999&other_field=value"  # 999 = legacy project

# Expected: HTTP 422 with validation error

# 3. Test with valid Lark project
curl -X POST http://your-app.test/your-endpoint \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-CSRF-TOKEN: your-token" \
  -d "project_id=1234&other_field=value"  # 1234 = Lark project

# Expected: HTTP 302 (redirect) or 200 (success)
```

---

## 🚨 Common Mistakes & Fixes

### ❌ Mistake 1: Lupa Filter Query

```php
// ❌ SALAH - akan include legacy projects di dropdown
$projects = Project::all();
// atau
$projects = Project::with('departments')->get();
```

**Fix**:

```php
// ✅ BENAR - hanya Lark projects
$projects = Project::fromLark()
    ->with('departments', 'status')
    ->notArchived()
    ->get();
```

---

### ❌ Mistake 2: Validation Rule Tidak Lengkap

```php
// ❌ SALAH - tidak ada ValidProjectSource
$request->validate([
    'project_id' => 'required|exists:projects,id',
]);
```

**Fix**:

```php
// ✅ BENAR
use App\Rules\ValidProjectSource;

$request->validate([
    'project_id' => ['required', 'exists:projects,id', new ValidProjectSource],
]);
```

---

### ❌ Mistake 3: Quick Add Project Tidak Sync ke Lark

```php
// ❌ SALAH - create project manual
Project::create([
    'name' => $request->name,
    'created_by' => auth()->user()->username,  // ← Akan jadi legacy!
]);
```

**Fix**:

```php
// ✅ BENAR - disable Quick Add atau sync ke Lark dulu
// Opsi 1: Disable Quick Add button di UI
// Opsi 2: Auto-sync ke Lark setelah create (via API)
// Opsi 3: Mark sebagai Lark jika memang dari Lark import
Project::create([
    'name' => $request->name,
    'created_by' => 'Sync from Lark',  // ← If from Lark
]);
```

---

### ❌ Mistake 4: Lupa Handle Nullable Project

```php
// ❌ SALAH - akan error jika project_id null
$request->validate([
    'project_id' => ['nullable', new ValidProjectSource],  // Tapi tidak ada 'exists'
]);
```

**Fix**:

```php
// ✅ BENAR
$request->validate([
    'project_id' => ['nullable', 'exists:projects,id', new ValidProjectSource],
]);
// Rule ValidProjectSource sudah handle null check internal
```

---

## 📊 Controllers yang Sudah Diupdate

Berikut controller yang **SUDAH** implement data governance (untuk referensi):

- [x] `MaterialRequestController` (create, store)
- [ ] `GoodsOutController` (TBD - perlu update)
- [ ] `GoodsInController` (TBD - perlu update)
- [ ] `MaterialPlanningController` (TBD - perlu update)
- [ ] `PurchaseRequestController` (TBD - perlu update)
- [ ] `TimingController` (TBD - perlu update)

**TODO**: Apply pattern yang sama ke controller-controller di atas.

---

## 🔗 Related Files

| File                                                   | Purpose                           |
| ------------------------------------------------------ | --------------------------------- |
| `app/Models/Production/Project.php`                    | Model dengan scopes & helpers     |
| `app/Rules/ValidProjectSource.php`                     | Custom validation rule            |
| `resources/views/components/project-options.blade.php` | Reusable component untuk dropdown |
| `README.md` (Section: Data Governance)                 | Dokumentasi lengkap               |

---

## 💡 Tips

1. **Copy-paste template di atas** untuk controller baru
2. **Gunakan component** (`@include('components.project-options')`) untuk konsistensi
3. **Test selalu** dengan legacy project ID via DevTools/Postman
4. **Log violations** untuk monitoring (optional tapi recommended)
5. **Update checklist** di atas setiap kali update controller

---

## ❓ Need Help?

Jika ada pertanyaan atau edge case yang belum tercakup:

1. Check README.md section "Data Governance"
2. Lihat implementasi di `MaterialRequestController` sebagai reference
3. Test dengan `php artisan tinker` untuk debug query

**Prinsip Dasar**: Jika ada `project_id` di form → wajib implement 3 langkah di atas.
