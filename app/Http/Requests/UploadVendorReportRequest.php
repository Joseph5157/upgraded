<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Validator;

class UploadVendorReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('ai_skip_reason');

        if (is_string($reason)) {
            $reason = trim($reason);
        }

        $this->merge([
            'ai_skipped' => $this->boolean('ai_skipped'),
            'ai_skip_reason' => $reason === '' ? null : $reason,
        ]);
    }

    public function rules(): array
    {
        return [
            'ai_skipped' => ['sometimes', 'boolean'],
            'ai_skip_reason' => ['nullable', 'string', 'max:255'],
            'ai_report' => ['nullable', 'file', 'mimes:pdf', 'max:102400'],
            'plag_report' => ['required', 'file', 'mimes:pdf', 'max:102400'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $aiSkipped = $this->boolean('ai_skipped');
            $hasAiReport = $this->hasFile('ai_report');
            $hasSkipReason = filled($this->input('ai_skip_reason'));

            if ($aiSkipped) {
                if ($hasAiReport) {
                    $validator->errors()->add('ai_report', 'Remove the AI report file when "AI report could not be generated" is checked.');
                }

                if (! $hasSkipReason) {
                    $validator->errors()->add('ai_skip_reason', 'Please explain why the AI report was unable to be generated.');
                }

                return;
            }

            if ($hasSkipReason) {
                $validator->errors()->add('ai_skip_reason', 'AI skip reason can only be provided when the AI report is skipped.');
            }

            if (! $hasAiReport) {
                $validator->errors()->add('ai_report', 'Please select the AI detection report PDF or provide a reason for skipping it.');
            }
        });
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return [
            'ai_report.required' => 'Please select the AI detection report PDF.',
            'ai_report.required_if' => 'Please select the AI detection report PDF.',
            'ai_report.required_unless' => 'Please select the AI detection report PDF or provide a reason for skipping it.',
            'ai_report.prohibited_if' => 'Remove the AI report file when "AI report could not be generated" is checked.',
            'ai_skip_reason.required' => 'Please explain why the AI report was unable to be generated.',
            'ai_skip_reason.required_if' => 'Please explain why the AI report was unable to be generated.',
            'ai_skip_reason.prohibited_unless' => 'AI skip reason can only be provided when the AI report is skipped.',
            'ai_report.file' => 'AI report must be a valid file.',
            'ai_report.uploaded' => 'AI report failed to upload. Keep each report under 100MB and try again.',
            'ai_report.mimes' => 'AI report must be a PDF file.',
            'ai_report.max' => 'AI report must be 100MB or smaller.',
            'plag_report.required' => 'Please select the plagiarism report PDF.',
            'plag_report.file' => 'Plagiarism report must be a valid file.',
            'plag_report.uploaded' => 'Plagiarism report failed to upload. Keep each report under 100MB and try again.',
            'plag_report.mimes' => 'Plagiarism report must be a PDF file.',
            'plag_report.max' => 'Plagiarism report must be 100MB or smaller.',
        ];
    }
}
