<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PART 4 - CREATE & EDIT TIMING VALIDATION
 * Production-ready validation with productivity fields
 */
class StoreTimingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'project_id' => 'nullable|exists:projects,id',
            'tanggal' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',

            // PART 4: Duration validation
            'duration_minutes' => 'required|integer|min:1',

            // PART 4: Measurement validation
            'measurement_type' => ['required', Rule::in(['qty', 'progress', 'pcs', 'unit', 'piece', 'item', 'set', 'meter', 'cm', 'kg', 'gram', 'percentage'])],
            'measurement_value' => 'required|numeric|min:0',

            // Other fields
            'status' => ['required', Rule::in(['complete', 'on progress', 'pending'])],
            'step' => 'nullable|string|max:255',
            'parts' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'job_order_id.required' => 'Please select a job order.',
            'job_order_id.exists' => 'Selected job order does not exist.',
            'duration_minutes.required' => 'Duration in minutes is required.',
            'duration_minutes.min' => 'Duration must be at least 1 minute.',
            'measurement_value.required' => 'Output value is required.',
            'measurement_value.min' => 'Output value cannot be negative.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }

    /**
     * Custom validation for progress-based measurement
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $measurementType = $this->input('measurement_type');
            $jobOrderId = $this->input('job_order_id');

            // If measurement_type is progress/percentage, validate job_order has total_standard_minutes
            if (in_array($measurementType, ['progress', 'percentage'])) {
                $jobOrder = \App\Models\Production\JobOrder::find($jobOrderId);

                if ($jobOrder && !$jobOrder->total_standard_minutes) {
                    $validator->errors()->add('measurement_type', 'Job order must have total standard minutes configured for progress-based measurement.');
                }
            }

            // If measurement_type is qty-based, validate job_order has standard_time_per_unit
            if (in_array($measurementType, ['qty', 'pcs', 'unit', 'piece', 'item', 'set', 'meter', 'cm', 'kg', 'gram'])) {
                $jobOrder = \App\Models\Production\JobOrder::find($jobOrderId);

                if ($jobOrder && !$jobOrder->standard_time_per_unit) {
                    $validator->errors()->add('measurement_type', 'Job order must have standard time per unit configured for quantity-based measurement.');
                }
            }

            // Validate employee is active
            $employee = \App\Models\Hr\Employee::find($this->input('employee_id'));
            if ($employee && $employee->status !== 'active') {
                $validator->errors()->add('employee_id', 'Selected employee is not active.');
            }

            // Prevent negative duration
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');
            if ($startTime && $endTime) {
                $start = \Carbon\Carbon::parse($this->input('tanggal') . ' ' . $startTime);
                $end = \Carbon\Carbon::parse($this->input('tanggal') . ' ' . $endTime);

                if ($end->lessThan($start)) {
                    $validator->errors()->add('end_time', 'End time cannot be earlier than start time.');
                }
            }
        });
    }
}
