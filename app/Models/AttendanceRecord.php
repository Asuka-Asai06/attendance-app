<?php

namespace App\Models;

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
    ];

    /**
     * 勤怠記録を所有するユーザー
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
     * 勤怠記録に紐づく修正申請
     */
    public function correctionRequests(): HasMany
    {
        return $this->hasMany(
            CorrectionRequest::class
        );
    }

    /**
     * 出勤時刻を取得する
     */
    public function getClockInAttribute(): ?string
    {
        return $this->clock_in_at?->format('H:i:s');
    }

    /**
     * 退勤時刻を取得する
     */
    public function getClockOutAttribute(): ?string
    {
        return $this->clock_out_at?->format('H:i:s');
    }

    /**
     * 休憩時間の合計を分で取得する
     */
    public function getTotalBreakMinutesAttribute(): int
    {
        return $this->breakTimes->sum(
            function ($breakTime): int {
                if ($breakTime->break_end_at === null) {
                    return 0;
                }

                return $breakTime->break_start_at
                    ->diffInMinutes($breakTime->break_end_at);
            }
        );
    }

    /**
     * 休憩時間の合計をHH:MMで返す
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $totalMinutes = $this->total_break_minutes;

        return sprintf(
            '%02d:%02d',
            intdiv($totalMinutes, 60),
            $totalMinutes % 60
        );
    }

    /**
     * 実働時間をHH:MMで返す
     */
    public function getTotalTimeAttribute(): ?string
    {
        if ($this->clock_out_at === null) {
            return null;
        }

        $workMinutes = $this->clock_in_at
            ->diffInMinutes($this->clock_out_at);

        $totalMinutes = max(
            0,
            $workMinutes - $this->total_break_minutes
        );

        return sprintf(
            '%02d:%02d',
            intdiv($totalMinutes, 60),
            $totalMinutes % 60
        );
    }

    /**
     * 修正申請のコメントを取得する
     */
    public function getCommentAttribute(): ?string
    {
        return $this->correctionRequests
            ->sortByDesc('created_at')
            ->first()?->comment;
    }
}
