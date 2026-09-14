<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;

class BreakTimeSeeder extends Seeder
{
    /**
     * 休憩記録のダミーデータを作成
     */
    public function run(): void
    {
        $attendanceRecords = AttendanceRecord::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        foreach ($attendanceRecords as $index => $attendanceRecord) {
            $this->createLunchBreak($attendanceRecord);

            if (
                $attendanceRecord->user->email !== 'user1@example.com'
                && $index % 3 === 0
            ) {
                $this->createShortBreak($attendanceRecord);
            }
        }
    }

    /**
     * 昼休憩を作成
     */
    private function createLunchBreak(AttendanceRecord $attendanceRecord): void
    {
        $breakStart = $attendanceRecord->clock_in_at
            ->copy()
            ->setTime(12, 0);

        $breakEnd = $attendanceRecord->clock_in_at
            ->copy()
            ->setTime(13, 0);

        BreakTime::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => $breakStart,
            'break_end_at' => $breakEnd,
        ]);
    }

    /**
     * 午後の短い休憩を作成
     */
    private function createShortBreak(AttendanceRecord $attendanceRecord): void
    {
        $breakStart = $attendanceRecord->clock_in_at
            ->copy()
            ->setTime(15, 30);

        $breakEnd = $attendanceRecord->clock_in_at
            ->copy()
            ->setTime(15, 45);

        BreakTime::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => $breakStart,
            'break_end_at' => $breakEnd,
        ]);
    }
}
