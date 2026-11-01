<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'changed_by',
        'old_status',
        'new_status',
        'note',
    ];

    // Laporan yang mengalami perubahan status
    public function report(): BelongsTo
    {
        return $this->belongsTo(WasteReport::class);
    }

    // Admin atau pengguna yang mengubah status
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}