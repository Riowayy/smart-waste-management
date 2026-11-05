<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('waste_categories', 'name')->ignore($category), ...($category->isDefault() ? [Rule::in([$category->name])] : [])],
            'slug' => $category->isDefault() ? ['sometimes', 'required', 'string', Rule::in([$category->slug])] : ['exclude'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.in' => 'Nama kategori bawaan tidak dapat diubah. Anda dapat mengubah deskripsinya.',
            'slug.in' => 'Slug kategori bawaan tidak dapat diubah.',
            'name.unique' => 'Nama kategori tersebut sudah digunakan kategori lain.',
            'description.max' => 'Deskripsi kategori tidak boleh lebih dari 5000 karakter.',
        ];
    }
}
