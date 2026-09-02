<?php

namespace App\Actions;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use RuntimeException;

class AttendanceAction
{
    /**
     * 出勤を登録する。
     */
    public function clockIn(User $user): AttendanceRecord
    {
        // すでに今日の勤怠がある場合は出勤できない
        if ($this->getTodayAttendance($user) !== null) {
            throw new RuntimeException('すでに出勤しています');
        }

        return $user->attendanceRecords()->create([
            'clock_in_at' => Carbon::now(),
        ]);
    }

    /**
     * 退勤を登録する。
     */
    public function clockOut(User $user): AttendanceRecord
    {
        $attendanceRecord = $this->getTodayAttendance($user);

        if ($attendanceRecord === null) {
            throw new RuntimeException('出勤していません');
        }

        if ($attendanceRecord->clock_out_at !== null) {
            throw new RuntimeException('すでに退勤しています');
        }

        // 未終了の休憩がある場合は退勤できない
        if ($this->getCurrentBreak($attendanceRecord) !== null) {
            throw new RuntimeException('休憩中は退勤できません');
        }

        $attendanceRecord->update([
            'clock_out_at' => Carbon::now(),
        ]);

        return $attendanceRecord;
    }

    /**
     * 休憩開始を登録する。
     */
    public function breakIn(User $user): BreakTime
    {
        $attendanceRecord = $this->getTodayAttendance($user);

        if ($attendanceRecord === null) {
            throw new RuntimeException('出勤していません');
        }

        if ($attendanceRecord->clock_out_at !== null) {
            throw new RuntimeException('退勤後は休憩を開始できません');
        }

        // すでに休憩中の場合は休憩開始できない
        if ($this->getCurrentBreak($attendanceRecord) !== null) {
            throw new RuntimeException('すでに休憩中です');
        }

        return $attendanceRecord->breakTimes()->create([
            'break_start_at' => Carbon::now(),
        ]);
    }

    /**
     * 休憩終了を登録する。
     */
    public function breakOut(User $user): BreakTime
    {
        $attendanceRecord = $this->getTodayAttendance($user);

        if ($attendanceRecord === null) {
            throw new RuntimeException('出勤していません');
        }

        if ($attendanceRecord->clock_out_at !== null) {
            throw new RuntimeException('すでに退勤しています');
        }

        $breakTime = $this->getCurrentBreak($attendanceRecord);

        if ($breakTime === null) {
            throw new RuntimeException('休憩中ではありません');
        }

        $breakTime->update([
            'break_end_at' => Carbon::now(),
        ]);

        return $breakTime;
    }

    /**
     * 今日の勤怠記録を取得する。
     */
    private function getTodayAttendance(User $user): ?AttendanceRecord
    {
        return $user->attendanceRecords()
            ->whereDate('clock_in_at', today())
            ->latest('clock_in_at')
            ->first();
    }

    /**
     * 現在休憩中の休憩記録を取得する。
     */
    private function getCurrentBreak(
        AttendanceRecord $attendanceRecord
    ): ?BreakTime {
        return $attendanceRecord->breakTimes()
            ->whereNull('break_end_at')
            ->latest('break_start_at')
            ->first();
    }
}
