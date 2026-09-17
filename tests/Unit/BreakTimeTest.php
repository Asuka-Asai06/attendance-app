<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use Tests\TestCase;

class BreakTimeTest extends TestCase
{
    public function test_勤怠記録とのリレーションが正しい(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $breakTime = BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        $this->assertTrue(
            $breakTime->attendanceRecord->is($attendanceRecord)
        );
    }
}
