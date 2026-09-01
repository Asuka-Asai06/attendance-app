<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    /**
     * ユーザーの勤怠記録。
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * ユーザーが申請した勤怠修正申請。
     */
    public function correctionRequests(): HasMany
    {
        return $this->hasMany(
            CorrectionRequest::class
        );
    }

    /**
     * ユーザーが承認した勤怠修正申請。
     */
    public function approvedCorrectionRequests(): HasMany
    {
        return $this->hasMany(
            CorrectionRequest::class,
            'approved_by'
        );
    }

    /**
     * 現在の勤怠状態を取得
     *
     * @return Attribute<string, never>
     */
    protected function attendanceStatus(): Attribute
    {
        return Attribute::get(function (): string {
            $attendanceRecord = $this->attendanceRecords()
                ->whereDate('clock_in_at', today())
                ->latest('clock_in_at')
                ->first();

            // 今日の勤怠記録が存在しない場合
            if ($attendanceRecord === null) {
                return AttendanceStatus::Outside->value;
            }

            // 退勤済みの場合
            if ($attendanceRecord->clock_out_at !== null) {
                return AttendanceStatus::Finished->value;
            }

            // 未終了の休憩が存在する場合
            if (
                $attendanceRecord->breakTimes()
                    ->whereNull('break_end_at')
                    ->exists()
            ) {
                return AttendanceStatus::OnBreak->value;
            }

            // 出勤中の場合
            return AttendanceStatus::Working->value;
        });
    }
}
