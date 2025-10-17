<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeDocument;
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
            ->get();

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $documentTypes = EmployeeDocument::getDocumentTypes();
        return view('employees.create', compact('departments', 'documentTypes'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->isReadOnlyAdmin()) {
            abort(403, 'You do not have permission to create employees.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'employee_no' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $formatted = \App\Models\Employee::formatEmployeeNo($value);
                        if (!\App\Models\Employee::validateEmployeeNo($value)) {
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
            'salary' => 'nullable|numeric|min:0',
            'saldo_cuti' => 'nullable|integer|min:0|max:365',
            'status' => 'required|in:active,inactive,terminated',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'document_types.*' => 'nullable|string',
            'document_names.*' => 'nullable|string|max:255',
            'document_descriptions.*' => 'nullable|string',
        ]);

        $employeeData = $request->only(['employee_no', 'name', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'salary', 'saldo_cuti', 'status', 'notes']);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employees/photos', 'public');
            $employeeData['photo'] = $photoPath;
        }

        $employee = Employee::create($employeeData);

        // Handle document uploads
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

        return redirect()->route('employees.index')->with('success', 'Employee successfully added.');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'documents',
            'timings' => function ($query) {
                $query->with('project')->latest()->limit(10);
            },
        ]);

        return view('employees.show', compact('employee'));
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
        $employee->load('documents');
        return view('employees.edit', compact('employee', 'departments', 'documentTypes'));
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
            'employee_no' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($employee) {
                    if (!empty($value)) {
                        $formatted = \App\Models\Employee::formatEmployeeNo($value);
                        if (!\App\Models\Employee::validateEmployeeNo($value, $employee->id)) {
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
            'salary' => 'nullable|numeric|min:0',
            'saldo_cuti' => 'nullable|integer|min:0|max:365',
            'status' => 'required|in:active,inactive,terminated',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'documents.*' => 'nullable|file|max:5120',
            'document_types.*' => 'nullable|string',
            'document_names.*' => 'nullable|string|max:255',
            'document_descriptions.*' => 'nullable|string',
        ]);

        $employeeData = $request->only(['employee_no', 'name', 'position', 'department_id', 'email', 'phone', 'address', 'gender', 'ktp_id', 'place_of_birth', 'date_of_birth', 'rekening', 'hire_date', 'salary', 'saldo_cuti', 'status', 'notes']);

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

    // View timing
    public function timing(Employee $employee)
    {
        $timings = $employee
            ->timings()
            ->with(['project.department'])
            ->paginate(50);
        return view('employees.timing', compact('employee', 'timings'));
    }
}
