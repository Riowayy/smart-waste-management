<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteCategory extends Model
{
    use HasFactory;

    public const DEFAULT_NAMES = [
        'organic-waste' => 'Organic Waste',
        'plastic-waste' => 'Plastic Waste',
        'paper-waste' => 'Paper Waste',
    ];

    public function isDefault(): bool
    {
        return array_key_exists($this->slug, self::DEFAULT_NAMES);
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // Laporan berdasarkan kategori yang dikonfirmasi
    public function reports(): HasMany
    {
        return $this->hasMany(WasteReport::class, 'category_id');
    }

    // Laporan berdasarkan prediksi AI
    public function aiPredictedReports(): HasMany
    {
        return $this->hasMany(WasteReport::class, 'ai_category_id');
    }
}
