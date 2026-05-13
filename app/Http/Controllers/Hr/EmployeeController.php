<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\FingerspotService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Models\Hr\Skillset;
use App\Models\Hr\SessionShift;
use App\Models\Hr\EmployeeDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Exports\EmployeeExport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('getLeaveBalance');
        $this->middleware('can:hr.employees.view')->except('getLeaveBalance');
        $this->middleware('can:hr.employees.create')->only(['create', 'store']);
        $this->middleware('can:hr.employees.edit')->only(['edit', 'update', 'deleteDocument', 'resolveContract', 'toggleProduction', 'toggleLeaderCapacity']);
        $this->middleware('can:hr.employees.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Auto-update expired contracts before displaying
        Employee::updateExpiredContracts();

        $employees = Employee::with(['department', 'documents'])
            ->withCount('documents')
            ->latest()
            ->get();

        // Active SP level per employee (for badge in table)
        $activeSpMap = \App\Models\Hr\WarningLetter::whereNotIn('status', ['expired', 'rejected'])
            ->where('valid_until', '>=', now()->toDateString())
            ->selectRaw('employee_id, MAX(sp_level) as max_sp')
            ->groupBy('employee_id')
            ->pluck('max_sp', 'employee_id');

        return view('hr.employees.index', compact('employees', 'activeSpMap'));
    }

    public function nearExpired()
    {
        Employee::updateExpiredContracts();

        $employees = Employee::with(['department', 'documents'])
            ->withCount('documents')
            ->latest()
            ->get();

        $nearExpiredIds = $employees->filter(function ($emp) {
            if (!$emp->contract_end_date || $emp->status !== 'active') return false;
            $days = now()->diffInDays($emp->contract_end_date, false);
            return $days >= 0 && $days <= 60;
        })->pluck('id')->toArray();

        $activeSpMap = \App\Models\Hr\WarningLetter::whereNotIn('status', ['expired', 'rejected'])
            ->where('valid_until', '>=', now()->toDateString())
            ->selectRaw('employee_id, MAX(sp_level) as max_sp')
            ->groupBy('employee_id')
            ->pluck('max_sp', 'employee_id');

        return view('hr.employees.index', compact('employees', 'nearExpiredIds', 'activeSpMap'))
            ->with('isNearExpired', true);
    }

    public function export(Request $request)
    {
        $status = $request->input('status', 'all');
        $filename = 'employees_' . ($status === 'all' ? 'all' : $status) . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new EmployeeExport($status), $filename);
    }

    public function create()
    {
        [$departments, $skillsets, $skillCategories, $proficiencyOptions, $sessionShifts] = $this->getFormData();
        $documentTypes = EmployeeDocument::getDocumentTypes();
        $employmentTypes = Employee::getEmploymentTypeOptions();

        return view('hr.employees.create', compact('departments', 'documentTypes', 'employmentTypes', 'skillsets', 'skillCategories', 'proficiencyOptions', 'sessionShifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'employment_type' => 'nullable|in:PKWT,PKWTT,Daily Worker,Probation,Internship,Working Trial',
            'citizenship' => 'nullable|in:WNI,WNA',
            'employee_no' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $formatted = \App\Models\Hr\Employee::formatEmployeeNo($value);
                        if (!\App\Models\Hr\Employee::validateEmployeeNo($value)) {
                            $fail("Employee number {$formatted} already exists.");
                        }
                    }
                },
            ],
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'email' => ['nullable', 'email', 'unique:employees,email'],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
            'ktp_id' => 'nullable|string|max:20|unique:employees,ktp_id',
            'place_of_birth' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'rekening' => 'nullable|string|max:30',
            'hire_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:hire_date',
            'salary' => 'nullable|numeric|min:0',
            'saldo_cuti' => 'nullable|numeric|min:0|max:999.99',
            'status' => 'required|in:active,inactive,pending_contract',
            'username' => 'nullable|string|max:255|unique:employees,username',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'document_types.*' => 'nullable|string',
            'document_names.*' => 'nullable|string|max:255',
            'document_descriptions.*' => 'nullable|string',
            'default_shift_id' => 'nullable|exists:session_shifts,id',
            'skillsets' => 'nullable|array',
            'skillsets.*' => 'exists:skillsets,id',
            'skillset_proficiency' => 'nullable|array',
            'skillset_proficiency.*' => 'in:basic,intermediate,advanced',
            'skillset_acquired_date' => 'nullable|array',
            'skillset_acquired_date.*' => 'nullable|date',
        ]);

        $employeeData = $request->only(['employee_no', 'name', 'username', 'employment_type', 'position', 'department_id', 'default_shift_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 'salary', 'saldo_cuti', 'status', 'notes']);
        $employeeData['is_production']     = $request->has('is_production') ? $request->boolean('is_production') : true;
        $employeeData['is_leader_capacity'] = $request->boolean('is_leader_capacity');

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employees/photos', 'public');
            $employeeData['photo'] = $photoPath;
        }

        $employee = Employee::create($employeeData);

        // Attach skillsets with pivot data
        if ($request->filled('skillsets')) {
            $skillsetData = [];
            foreach ($request->skillsets as $index => $skillsetId) {
                $skillsetData[$skillsetId] = [
                    'proficiency_level' => $request->skillset_proficiency[$index] ?? 'basic',
                    'acquired_date' => $request->skillset_acquired_date[$index] ?? now(),
                    'last_used_date' => now(),
                ];
            }
            $employee->skillsets()->attach($skillsetData);
        }

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filePath = $file->store('employees/documents', 'public');
                    EmployeeDocument::create([
                        'employee_id' => $employee->id,
                        'document_type' => $request->document_types[$index] ?? 'others',
                        'document_name' => $request->document_names[$index] ?? $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'description' => $request->document_descriptions[$index] ?? null,
                    ]);
                }
            }
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee successfully added with ' . count($request->skillsets ?? []) . ' skillset(s).');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'defaultShift',
            'documents',
            'skillsets',
            'timings' => function ($query) {
                $query->with('project')->latest()->limit(10);
            },
        ]);

        return view('hr.employees.show', compact('employee'));
    }

    public function downloadDocument(EmployeeDocument $document)
    {
        try {
            if (!Storage::disk('public')->exists($document->file_path)) {
                abort(404, 'File not found');
            }

            $employee = $document->employee;

            // Create dynamic filename: {employee_name}_{document_type}_{document_name}
            $employeeName = str_replace(' ', '_', $employee->name);
            $documentType = strtoupper($document->document_type);
            $documentName = str_replace(' ', '_', $document->document_name);

            // Get file extension
            $fileExtension = pathinfo($document->file_path, PATHINFO_EXTENSION);

            // Create filename: John_Doe_KTP_ID_Card_Copy.pdf
            $filename = "{$employeeName}_{$documentType}_{$documentName}.{$fileExtension}";

            // Clean filename (remove special characters)
            $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);

            return Storage::disk('public')->download($document->file_path, $filename);
        } catch (\Exception $e) {
            \Log::error('Document download error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Failed to download document: ' . $e->getMessage());
        }
    }

    public function getDocuments(Employee $employee)
    {
        try {
            $documents = $employee->documents()->orderBy('created_at', 'desc')->get();

            $documentsData = $documents->map(function ($document) {
                $documentTypes = EmployeeDocument::getDocumentTypes();

                return [
                    'id' => $document->id,
                    'document_name' => $document->document_name,
                    'document_type' => $document->document_type,
                    'document_type_label' => $documentTypes[$document->document_type] ?? ucwords(str_replace('_', ' ', $document->document_type)),
                    'file_size' => $document->file_size,
                    'file_url' => $document->file_url,
                    'mime_type' => $document->mime_type,
                    'description' => $document->description,
                    'created_at' => $document->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'documents' => $documentsData,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching employee documents: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to load documents',
                ],
                500,
            );
        }
    }

    public function toggleProduction(Employee $employee)
    {
        $this->authorize('hr.employees.edit');
        $employee->update(['is_production' => !$employee->is_production]);
        return response()->json(['is_production' => $employee->is_production]);
    }

    public function toggleLeaderCapacity(Employee $employee)
    {
        $this->authorize('hr.employees.edit');
        $employee->update(['is_leader_capacity' => !$employee->is_leader_capacity]);
        return response()->json(['is_leader_capacity' => $employee->is_leader_capacity]);
    }

    public function edit(Employee $employee)
    {
        [$departments, $skillsets, $skillCategories, $proficiencyOptions, $sessionShifts] = $this->getFormData();
        $documentTypes = EmployeeDocument::getDocumentTypes();
        $employmentTypes = Employee::getEmploymentTypeOptions();
        $employee->load('documents', 'skillsets');

        return view('hr.employees.edit', compact('employee', 'departments', 'documentTypes', 'employmentTypes', 'skillsets', 'skillCategories', 'proficiencyOptions', 'sessionShifts'));
    }

    /** Shared form data — cached 10 menit karena jarang berubah */
    private function getFormData(): array
    {
        $departments = Cache::remember('form_departments', 600, fn() => Department::orderBy('name')->get());

        $skillsets = Cache::remember(
            'form_skillsets',
            600,
            fn() => Skillset::active()
                ->select(['id', 'name', 'category'])
                ->orderBy('name')
                ->get(),
        );

        $sessionShifts = Cache::remember(
            'form_session_shifts',
            600,
            fn() => SessionShift::with('department')
                ->where('is_active', true)
                ->orderByRaw('department_id IS NULL DESC')
                ->orderBy('department_id')
                ->orderBy('type_of_shift')
                ->get(),
        );

        $skillCategories = Skillset::getCategoryOptions();
        $proficiencyOptions = Skillset::getProficiencyOptions();

        return [$departments, $skillsets, $skillCategories, $proficiencyOptions, $sessionShifts];
    }

    public function update(Request $request, Employee $employee)
    {

        // Check if this is a document upload request from modal
        if ($request->hasFile('documents') && $request->filled('document_types') && !$request->filled('name')) {
            return $this->handleDocumentUpload($request, $employee);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'employment_type' => 'required|in:PKWT,PKWTT,Daily Worker,Probation,Internship,Working Trial',
            'employee_no' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($employee) {
                    if (!empty($value)) {
                        $formatted = \App\Models\Hr\Employee::formatEmployeeNo($value);
                        if (!\App\Models\Hr\Employee::validateEmployeeNo($value, $employee->id)) {
                            $fail("Employee number {$formatted} already exists.");
                        }
                    }
                },
            ],
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'email' => ['nullable', 'email', Rule::unique('employees')->ignore($employee->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female',
            'ktp_id' => ['nullable', 'string', 'max:20', Rule::unique('employees')->ignore($employee->id)],
            'place_of_birth' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'rekening' => 'nullable|string|max:30',
            'hire_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:hire_date',
            'salary' => 'nullable|numeric|min:0',
            'saldo_cuti' => 'nullable|numeric|min:0|max:999.99',
            'status' => 'required|in:active,inactive,pending_contract',
            'username' => ['nullable', 'string', 'max:255', Rule::unique('employees')->ignore($employee->id)],
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'document_types.*' => 'nullable|string',
            'document_names.*' => 'nullable|string|max:255',
            'document_descriptions.*' => 'nullable|string',
            'default_shift_id' => 'nullable|exists:session_shifts,id',
            'skillsets' => 'nullable|array',
            'skillsets.*' => 'exists:skillsets,id',
            'skillset_proficiency' => 'nullable|array',
            'skillset_proficiency.*' => 'in:basic,intermediate,advanced',
            'skillset_acquired_date' => 'nullable|array',
            'skillset_acquired_date.*' => 'nullable|date',
        ]);

        $employeeData = $request->only(['employee_no', 'name', 'username', 'employment_type', 'position', 'department_id', 'default_shift_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 'salary', 'saldo_cuti', 'status', 'notes']);
        $employeeData['is_production']     = $request->boolean('is_production');
        $employeeData['is_leader_capacity'] = $request->boolean('is_leader_capacity');

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $photoPath = $request->file('photo')->store('employees/photos', 'public');
            $employeeData['photo'] = $photoPath;
        }

        $employee->update($employeeData);

        // Sync skillsets with pivot data
        if ($request->has('skillsets')) {
            $skillsetData = [];
            if ($request->filled('skillsets')) {
                foreach ($request->skillsets as $index => $skillsetId) {
                    $skillsetData[$skillsetId] = [
                        'proficiency_level' => $request->skillset_proficiency[$index] ?? 'basic',
                        'acquired_date' => $request->skillset_acquired_date[$index] ?? now(),
                        'last_used_date' => now(),
                    ];
                }
            }
            $employee->skillsets()->sync($skillsetData);
        }

        // Handle new document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filePath = $file->store('employees/documents', 'public');

                    EmployeeDocument::create([
                        'employee_id' => $employee->id,
                        'document_type' => $request->document_types[$index] ?? 'lainnya',
                        'document_name' => $request->document_names[$index] ?? $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'description' => $request->document_descriptions[$index] ?? null,
                    ]);
                }
            }
        }

        // Check if request came from index page with active filters
        if ($request->has('return_to_index')) {
            return redirect()->route('employees.index')->with('success', 'Employee successfully updated.');
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Employee successfully updated.');
    }

    private function handleDocumentUpload(Request $request, Employee $employee)
    {
        try {
            $request->validate([
                'documents.*' => 'required|file|max:5120', // 5MB
                'document_types.*' => 'required|string',
                'document_names.*' => 'required|string|min:3|max:255',
                'document_descriptions.*' => 'nullable|string',
            ]);

            $uploadedDocuments = [];

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $index => $file) {
                    if ($file && $file->isValid()) {
                        $filePath = $file->store('employees/documents', 'public');

                        $document = EmployeeDocument::create([
                            'employee_id' => $employee->id,
                            'document_type' => $request->document_types[$index],
                            'document_name' => $request->document_names[$index],
                            'file_path' => $filePath,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'description' => $request->document_descriptions[$index] ?? null,
                        ]);

                        $uploadedDocuments[] = $document;
                    }
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully!',
                    'documents' => $uploadedDocuments,
                ]);
            }

            return redirect()->route('employees.show', $employee)->with('success', 'Document uploaded successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                    ],
                    422,
                );
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Document upload error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Upload failed: ' . $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint AJAX untuk autocomplete pencarian karyawan aktif.
     * GET /hr/employees/search?q=nama&limit=10
     */
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        $limit = min((int) $request->input('limit', 10), 50);

        $employees = Employee::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")->orWhere('employee_no', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'employee_no', 'name']);

        return response()->json($employees);
    }

    public function checkEmployeeNo(Request $request)
    {
        $employeeNo = $request->input('employee_no');
        $employeeId = $request->input('employee_id');

        $available = Employee::validateEmployeeNo($employeeNo, $employeeId);

        return response()->json([
            'available' => $available,
            'formatted' => Employee::formatEmployeeNo($employeeNo),
        ]);
    }

    public function checkKtpId(Request $request)
    {
        $ktpId = $request->input('ktp_id');
        $employeeId = $request->input('employee_id');

        $query = Employee::where('ktp_id', $ktpId);

        if ($employeeId) {
            $query->where('id', '!=', $employeeId);
        }

        $available = !$query->exists();

        return response()->json([
            'available' => $available,
        ]);
    }

    /**
     * Get employee leave balance
     */
    public function getLeaveBalance(Employee $employee)
    {
        return response()->json([
            'success' => true,
            'balance' => $employee->saldo_cuti ?? 0,
            'employee_name' => $employee->name,
        ]);
    }

    // View timing
    public function timing(Employee $employee)
    {
        $timings = $employee
            ->timings()
            ->with(['project.departments'])
            ->latest()
            ->paginate(50);
        return view('hr.employees.timing', compact('employee', 'timings'));
    }

    public function deleteDocument(EmployeeDocument $document)
    {
        try {
            $employeeId = $document->employee_id;

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Delete document record
            $document->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document deleted successfully.',
                ]);
            }

            return redirect()->route('employees.show', $employeeId)->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Document deletion error: ' . $e->getMessage());

            if (request()->expectsJson()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Failed to delete document: ' . $e->getMessage(),
                    ],
                    500,
                );
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    public function resolveContract(Request $request, Employee $employee)
    {
        $this->authorize('hr.employees.edit');

        $request->validate([
            'action'            => 'required|in:extend,terminate',
            'contract_end_date' => 'required_if:action,extend|nullable|date|after:today',
        ]);

        if ($request->action === 'extend') {
            $note = "[HR Action] Contract extended until {$request->contract_end_date} by " . auth()->user()->name . " on " . now()->format('Y-m-d');
            $employee->update([
                'status'            => 'active',
                'contract_end_date' => $request->contract_end_date,
                'notes'             => trim(($employee->notes ?? '') . "\n" . $note),
            ]);
            return redirect()->back()->with('success', "Contract extended for {$employee->name}. Status is now Active.");
        }

        $note = "[HR Action] Employment set to inactive by " . auth()->user()->name . " on " . now()->format('Y-m-d');
        $employee->update([
            'status' => 'inactive',
            'notes'  => trim(($employee->notes ?? '') . "\n" . $note),
        ]);
        return redirect()->back()->with('success', "{$employee->name} has been set as Inactive.");
    }

    public function destroy(Employee $employee)
    {
        // Delete photo
        if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
            Storage::disk('public')->delete($employee->photo);
        }

        // Delete documents
        foreach ($employee->documents as $document) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
        }

        // Hapus dari mesin fingerspot jika terdaftar (abaikan error agar tidak blokir proses)
        if ($employee->device_registered_at && str_starts_with($employee->employee_no, 'DCM-')) {
            try {
                $deviceId = config('fingerspot.device_id');
                if ($deviceId) {
                    $pin = ltrim(substr($employee->employee_no, 4), '0') ?: '0';
                    app(FingerspotService::class)->deleteUserinfo($deviceId, $pin);
                }
            } catch (\Exception $e) {
                Log::warning('Gagal hapus employee dari fingerspot: ' . $e->getMessage(), [
                    'employee_no' => $employee->employee_no,
                ]);
            }
        }

        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee successfully deleted.');
    }
}
