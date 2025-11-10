<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Admin\Department;
use App\Models\Hr\Skillset;
use App\Models\Hr\EmployeeDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Admin HR, Super Admin, dan Admin (read-only) bisa akses
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin', 'admin_hr', 'admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized access to HR module.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::with(['department', 'documents'])
            ->withCount('documents')
            ->latest()
            ->get();

        return view('hr.employees.index', compact('employees'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $documentTypes = EmployeeDocument::getDocumentTypes();
        $employmentTypes = Employee::getEmploymentTypeOptions();
        $skillsets = Skillset::active()->orderBy('name')->get();
        $skillCategories = Skillset::getCategoryOptions();
        $proficiencyOptions = Skillset::getProficiencyOptions();

        return view('hr.employees.create', compact('departments', 'documentTypes', 'employmentTypes', 'skillsets', 'skillCategories', 'proficiencyOptions'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create employees.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'employment_type' => 'nullable|in:PKWT,PKWTT,Daily Worker,Probation',
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
            'status' => 'required|in:active,inactive,terminated',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'document_types.*' => 'nullable|string',
            'document_names.*' => 'nullable|string|max:255',
            'document_descriptions.*' => 'nullable|string',
            'skillsets' => 'nullable|array',
            'skillsets.*' => 'exists:skillsets,id',
            'skillset_proficiency' => 'nullable|array',
            'skillset_proficiency.*' => 'in:basic,intermediate,advanced',
            'skillset_acquired_date' => 'nullable|array',
            'skillset_acquired_date.*' => 'nullable|date',
        ]);

        $employeeData = $request->only(['employee_no', 'name', 'employment_type', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 'salary', 'saldo_cuti', 'status', 'notes']);

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

    public function edit(Employee $employee)
    {
        $departments = Department::orderBy('name')->get();
        $documentTypes = EmployeeDocument::getDocumentTypes();
        $employmentTypes = Employee::getEmploymentTypeOptions();
        $skillsets = Skillset::active()->orderBy('name')->get();
        $skillCategories = Skillset::getCategoryOptions();
        $proficiencyOptions = Skillset::getProficiencyOptions();
        $employee->load('documents', 'skillsets'); // load skillsets

        return view('hr.employees.edit', compact('employee', 'departments', 'documentTypes', 'employmentTypes', 'skillsets', 'skillCategories', 'proficiencyOptions'));
    }

    public function update(Request $request, Employee $employee)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to edit employees.');
        }

        // Check if this is a document upload request from modal
        if ($request->hasFile('documents') && $request->filled('document_types') && !$request->filled('name')) {
            return $this->handleDocumentUpload($request, $employee);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'employment_type' => 'required|in:PKWT,PKWTT,Daily Worker,Probation',
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
            'status' => 'required|in:active,inactive,terminated',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'document_types.*' => 'nullable|string',
            'document_names.*' => 'nullable|string|max:255',
            'document_descriptions.*' => 'nullable|string',
            'skillsets' => 'nullable|array',
            'skillsets.*' => 'exists:skillsets,id',
            'skillset_proficiency' => 'nullable|array',
            'skillset_proficiency.*' => 'in:basic,intermediate,advanced',
            'skillset_acquired_date' => 'nullable|array',
            'skillset_acquired_date.*' => 'nullable|date',
        ]);

        $employeeData = $request->only(['employee_no', 'name', 'employment_type', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'contract_end_date', 'salary', 'saldo_cuti', 'status', 'notes']);

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
        if (Auth::user()->isReadOnlyAdmin()) {
            return redirect()->route('employees.show', $document->employee_id)->with('error', 'You do not have permission to delete documents.');
        }

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

    public function destroy(Employee $employee)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to delete employees.');
        }

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

        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee successfully deleted.');
    }
}
