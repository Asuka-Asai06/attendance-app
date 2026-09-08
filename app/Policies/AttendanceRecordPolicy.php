<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 他人の勤怠詳細を見ることはできない
     */
    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->admin_status
            || $user->id === $attendanceRecord->user_id;
    }

    /**
     * 管理者以外は他人の勤怠を修正することはできない
     *
     * @param  AttendanceRecord  $attendanceRecord  修正対象の勤怠
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->admin_status
            || $user->id === $attendanceRecord->user_id;
    }
}
