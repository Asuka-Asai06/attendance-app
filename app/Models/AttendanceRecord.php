<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in_at',
        'clock_out_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'status' => AttendanceStatus::class,
    ];

    /**
     * 勤怠記録を所有するユーザー。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 勤怠記録に紐づく休憩時間
     */
    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 勤怠記録に紐づく修正申請。
     */
    public function correctionRequests(): HasMany
    {
        return $this->hasMany(
            CorrectionRequest::class
        );
    }
}
