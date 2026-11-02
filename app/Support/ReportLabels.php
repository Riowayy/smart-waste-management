<?php

namespace App\Support;

use App\Models\WasteCategory;

final class ReportLabels
{
    public const CATEGORIES = [
        'organic-waste' => 'Sampah Organik',
        'plastic-waste' => 'Sampah Plastik',
        'paper-waste' => 'Sampah Kertas',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu',
        'in_progress' => 'Dalam Proses',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak',
    ];

    public static function status(string $status): string
    {
        $key = strtolower(str_replace(' ', '_', $status));

        return self::STATUSES[$key] ?? $status;
    }

    public static function category(?WasteCategory $category, string $fallback = 'Belum ditentukan'): string
    {
        return $category === null ? $fallback : (self::CATEGORIES[$category->slug] ?? $category->name);
    }

    public static function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'exists' => ':attribute yang dipilih tidak tersedia.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.file' => ':attribute maksimal :max KB.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat :values.',
            'numeric' => ':attribute harus berupa angka.',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'in' => ':attribute yang dipilih tidak valid.',
            'uploaded' => ':attribute gagal diunggah.',
        ];
    }

    public static function validationAttributes(): array
    {
        return ['user_id' => 'Pelapor', 'category_id' => 'Kategori', 'description' => 'Deskripsi laporan',
            'image' => 'Foto laporan', 'latitude' => 'Lintang', 'longitude' => 'Bujur', 'status' => 'Status', 'note' => 'Catatan'];
    }
}
