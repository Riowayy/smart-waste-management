<?php

namespace App\Services;

use App\Models\WasteCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

class WasteClassifier
{
    private const CATEGORIES = [
        'organic-waste' => 'Organic Waste',
        'plastic-waste' => 'Plastic Waste',
        'paper-waste' => 'Paper Waste',
    ];

    /** @return array{ai_category_id?: int, ai_confidence?: float} */
    public function predict(UploadedFile $image): array
    {
        $url = config('services.ml.url');
        if (! is_string($url) || trim($url) === '') {
            return [];
        }

        try {
            $response = Http::acceptJson()->withoutRedirecting()
                ->connectTimeout(2)->timeout(5)
                ->attach('image', $image->getContent(), $image->getClientOriginalName(), [
                    'Content-Type' => $image->getMimeType(),
                ])
                ->post(rtrim(trim($url), '/').'/predict');

            if (! $response->successful()) {
                return [];
            }

            $prediction = $response->json();
            $slug = is_array($prediction) ? ($prediction['slug'] ?? null) : null;
            $confidence = is_array($prediction) ? ($prediction['confidence'] ?? null) : null;
            if (! is_string($slug) || ! isset(self::CATEGORIES[$slug])
                || ($prediction['category'] ?? null) !== self::CATEGORIES[$slug]
                || (! is_int($confidence) && ! is_float($confidence))
                || ! is_finite((float) $confidence) || $confidence < 0 || $confidence > 1) {
                return [];
            }

            $categoryId = WasteCategory::query()->where('slug', $slug)->value('id');
            if ($categoryId === null) {
                return [];
            }

            return [
                'ai_category_id' => (int) $categoryId,
                // FastAPI memakai probabilitas 0-1; schema menyimpan persen 0-100.
                'ai_confidence' => round($confidence * 100, 2),
            ];
        } catch (Throwable) {
            // Prediksi bersifat opsional; kegagalan ML tidak membatalkan laporan.
            return [];
        }
    }
}
