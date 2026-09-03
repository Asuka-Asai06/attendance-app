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
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
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

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AttendanceRecord $attendanceRecord): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        //
    }
}
