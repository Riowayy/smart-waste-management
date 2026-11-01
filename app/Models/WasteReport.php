<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'ai_category_id',
        'description',
        'image_path',
        'latitude',
        'longitude',
        'status',
        'ai_confidence',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'ai_confidence' => 'float',
    ];

    // Pemilik laporan
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Kategori yang dikonfirmasi
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            WasteCategory::class,
            'category_id'
        );
    }

    // Kategori hasil prediksi AI
    public function aiCategory(): BelongsTo
    {
        return $this->belongsTo(
            WasteCategory::class,
            'ai_category_id'
        );
    }

    // Riwayat perubahan status
    public function statusHistories(): HasMany
    {
        return $this->hasMany(
            ReportStatusHistory::class,
            'report_id'
        );
    }
}