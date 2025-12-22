<?php

namespace NikunjKothiya\QueueMonitor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClearFailuresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? $this->user()->can('viewQueueMonitor') : true;
    }

    public function rules(): array
    {
        return [
            'confirm' => ['required', 'in:yes'],
        ];
    }
}


