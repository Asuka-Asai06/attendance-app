<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;

class BreakTimeSeeder extends Seeder
{
    /**
     * 休憩時間のダミーデータを作成する
     */
    public function run(): void
    {
        $attendanceRecords = AttendanceRecord::query()->get();

        foreach ($attendanceRecords as $attendanceRecord) {
            BreakTime::create([
                'attendance_record_id' => $attendanceRecord->id,
                'break_start_at' => $attendanceRecord->clock_in_at->copy()->setTime(12, 0),
                'break_end_at' => $attendanceRecord->clock_in_at->copy()->setTime(13, 0),
            ]);
        }
    }
}
