<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWasteReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function messages(): array
    {
        return \App\Support\ReportLabels::validationMessages();
    }

    public function attributes(): array
    {
        return \App\Support\ReportLabels::validationAttributes();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:waste_categories,id'],
            'description' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
