<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_start_at',
        'break_end_at',
    ];

    protected $casts = [
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
    ];

    /**
     * 修正申請とのリレーション。
     */
    public function CorrectionRequest(): BelongsTo
    {
        return $this->belongsTo(
            CorrectionRequest::class
        );
    }
}
