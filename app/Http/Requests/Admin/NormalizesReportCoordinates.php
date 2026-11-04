<?php

namespace App\Http\Requests\Admin;

use App\Support\ReportLabels;

trait NormalizesReportCoordinates
{
    protected function prepareForValidation(): void
    {
        foreach (['latitude' => 'NS', 'longitude' => 'EW'] as $field => $directions) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);
            // DMS tidak valid dipertahankan agar ditolak aturan numeric yang sudah ada.
            $pattern = '/\A(\d{1,3})\s*°\s*(\d{1,2})\s*[\x{0027}′’]\s*(\d{1,2}(?:\.\d+)?)\s*[\x{0022}″”]\s*(['.$directions.'])\z/iu';
            if (preg_match($pattern, $value, $parts) === 1 && (int) $parts[2] < 60 && (float) $parts[3] < 60) {
                $decimal = (int) $parts[1] + (int) $parts[2] / 60 + (float) $parts[3] / 3600;
                $value = in_array(strtoupper($parts[4]), ['S', 'W'], true) ? -$decimal : $decimal;
            }

            $this->merge([$field => $value === '' ? null : $value]);
        }
    }

    public function messages(): array
    {
        return array_replace(ReportLabels::validationMessages(), [
            'latitude.numeric' => 'Lintang harus berupa desimal (contoh -21.156389) atau DMS (contoh 21°9\'23.0"S). Gunakan N/S, serta menit dan detik kurang dari 60.',
            'longitude.numeric' => 'Bujur harus berupa desimal (contoh 115.484083) atau DMS (contoh 115°29\'02.7"E). Gunakan E/W, serta menit dan detik kurang dari 60.',
            'latitude.between' => 'Lintang harus antara -90 dan 90 derajat, termasuk setelah konversi DMS.',
            'longitude.between' => 'Bujur harus antara -180 dan 180 derajat, termasuk setelah konversi DMS.',
        ]);
    }
}
