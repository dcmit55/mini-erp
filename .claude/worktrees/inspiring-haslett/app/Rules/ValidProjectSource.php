<?php

namespace App\Rules;

use App\Models\Production\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation Rule: Valid Project Source
 *
 * Memastikan project_id yang digunakan berasal dari Lark (created_by = 'Sync from Lark')
 * Project legacy TIDAK BOLEH digunakan dalam proses bisnis apa pun
 *
 * Penggunaan:
 * 'project_id' => ['required', 'exists:projects,id', new ValidProjectSource]
 */
class ValidProjectSource implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow null/empty (handled by required validation)
        if (empty($value)) {
            return;
        }

        // Find project
        $project = Project::find($value);

        // Check if project exists and is from Lark
        if (!$project) {
            $fail('The selected project does not exist.');
            return;
        }

        if (!$project->isFromLark()) {
            $fail('The selected project is a legacy project and cannot be used. Please select a project synced from Lark.');
            return;
        }

        // Additional check: project must not be soft deleted
        if ($project->trashed()) {
            $fail('The selected project has been deleted and cannot be used.');
            return;
        }
    }
}
