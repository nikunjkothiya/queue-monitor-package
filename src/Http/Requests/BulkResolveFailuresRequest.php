<?php

namespace NikunjKothiya\QueueMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkResolveFailuresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }
}


