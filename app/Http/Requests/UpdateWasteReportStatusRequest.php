<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWasteReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,in_progress,completed,rejected'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
