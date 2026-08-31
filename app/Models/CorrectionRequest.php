<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'user_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'comment',
        'approval_status',
        'approved_by',
        'approved_at',
        'application_date',
    ];

    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at' => 'datetime',
        'application_date' => 'datetime',
        'approval_status' => ApprovalStatus::class,
    ];

    /**
     * 修正対象の勤怠記録
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * 修正申請を行ったユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 申請を承認した管理者ユーザー
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 修正対象の休憩情報
     */
    public function breakTimes(): HasMany
    {
        return $this->hasMany(
            CorrectionBreak::class
        );
    }
}
