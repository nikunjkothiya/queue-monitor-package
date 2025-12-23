<?php

namespace NikunjKothiya\QueueMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveFailureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('viewQueueMonitor') : true;
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => ['nullable', 'string'],
        ];
    }
}


