<?php

namespace NikunjKothiya\QueueMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetryWithPayloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'string'],
            'retry_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.required' => 'The payload is required.',
        ];
    }

    /**
     * Validate that the payload is valid JSON after standard validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $payload = $this->input('payload');
            
            if ($payload) {
                json_decode($payload);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validator->errors()->add('payload', 'The payload must be valid JSON. Error: ' . json_last_error_msg());
                }
            }
        });
    }

    /**
     * Get the decoded payload.
     */
    public function getDecodedPayload(): ?array
    {
        $payload = $this->input('payload');
        return $payload ? json_decode($payload, true) : null;
    }
}
