<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'document_type', 'document_name', 'file_path', 'file_size', 'mime_type', 'description'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Accessor untuk file URL
    public function getFileUrlAttribute()
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return Storage::url($this->file_path);
        }
        return '#';
    }

    // Accessor untuk formatted file size
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return '-';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Accessor untuk download filename
    public function getDownloadFilenameAttribute()
    {
        $employeeName = str_replace(' ', '_', $this->employee->name);
        $documentType = strtoupper($this->document_type);
        $documentName = str_replace(' ', '_', $this->document_name);

        // Get file extension
        $fileExtension = pathinfo($this->file_path, PATHINFO_EXTENSION);

        // Create filename: John_Doe_KTP_ID_Card_Copy.pdf
        $filename = "{$employeeName}_{$documentType}_{$documentName}.{$fileExtension}";

        // Clean filename (remove special characters)
        return preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
    }

    // accessor untuk formatted document type
    public function getFormattedDocumentTypeAttribute()
    {
        $types = self::getDocumentTypes();
        return $types[$this->document_type] ?? ucwords(str_replace('_', ' ', $this->document_type));
    }

    // Static method untuk document types
    public static function getDocumentTypes()
    {
        return [
            'ktp' => 'KTP/ID Card',
            'ijazah' => 'Ijazah/Certificate',
            'cv' => 'CV/Resume',
            'surat_pengalaman' => 'Surat Pengalaman Kerja',
            'surat_sehat' => 'Surat Keterangan Sehat',
            'skck' => 'SKCK',
            'foto' => 'Foto',
            'npwp' => 'NPWP',
            'bpjs' => 'BPJS',
            'kontrak' => 'Kontrak Kerja',
            'lainnya' => 'Lainnya',
        ];
    }
}
