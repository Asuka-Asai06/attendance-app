<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Models\User;
use Tests\TestCase;

class AttendanceRecordTest extends TestCase
{
    /**
     * 勤怠記録がユーザーに紐づいていることを確認する。
     */
    public function test_ユーザーとのリレーションが正しい(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $attendanceRecord->user->is($user)
        );
    }

    /**
     * 勤怠記録が複数の休憩時間を持つことを確認する。
     */
    public function test_休憩時間とのリレーションが正しい(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $breakTimes = BreakTime::factory()
            ->count(2)
            ->create([
                'attendance_record_id' => $attendanceRecord->id,
            ]);

        $this->assertCount(2, $attendanceRecord->breakTimes);

        $this->assertTrue(
            $attendanceRecord->breakTimes->contains(
                fn ($break) => $break->is($breakTimes[0])
            )
        );

        $this->assertTrue(
            $attendanceRecord->breakTimes->contains(
                fn ($break) => $break->is($breakTimes[1])
            )
        );
    }

    /**
     * 勤怠記録が複数の修正申請を持つことを確認する。
     */
    public function test_修正申請とのリレーションが正しい(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $correctionRequests = CorrectionRequest::factory()
            ->count(2)
            ->create([
                'attendance_record_id' => $attendanceRecord->id,
            ]);

        $this->assertCount(2, $attendanceRecord->correctionRequests);

        $this->assertTrue(
            $attendanceRecord->correctionRequests->contains(
                fn ($request) => $request->is($correctionRequests[0])
            )
        );

        $this->assertTrue(
            $attendanceRecord->correctionRequests->contains(
                fn ($request) => $request->is($correctionRequests[1])
            )
        );
    }

    /**
     * 出勤時刻をH:i:s形式で取得できることを確認する。
     */
    public function test_出勤時刻を取得できる(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'clock_in_at' => '2026-09-17 09:00:00',
        ]);

        $this->assertSame(
            '09:00:00',
            $attendanceRecord->clock_in
        );
    }

    /**
     * 退勤時刻をH:i:s形式で取得できることを確認する。
     */
    public function test_退勤時刻を取得できる(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'clock_out_at' => '2026-09-17 18:00:00',
        ]);

        $this->assertSame(
            '18:00:00',
            $attendanceRecord->clock_out
        );
    }

    /**
     * 退勤時刻が未登録の場合はnullを返すことを確認する。
     */
    public function test_退勤時刻が未登録の場合はnullを返す(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'clock_out_at' => null,
        ]);

        $this->assertNull(
            $attendanceRecord->clock_out
        );
    }

    /**
     * 休憩時間の合計を分単位で取得できることを確認する。
     */
    public function test_休憩時間の合計を分で取得できる(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-17 12:00:00',
            'break_end_at' => '2026-09-17 13:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-17 15:00:00',
            'break_end_at' => '2026-09-17 15:30:00',
        ]);

        $this->assertSame(
            90,
            $attendanceRecord->total_break_minutes
        );
    }

    /**
     * 休憩終了時刻が未登録の場合は0分として扱うことを確認する。
     */
    public function test_休憩終了時刻が未登録の場合は0分として扱う(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-17 12:00:00',
            'break_end_at' => null,
        ]);

        $this->assertSame(
            0,
            $attendanceRecord->total_break_minutes
        );
    }

    /**
     * 休憩時間の合計をHH:MM形式で取得できることを確認する。
     */
    public function test_休憩時間の合計を_hhm_m形式で取得できる(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-17 12:00:00',
            'break_end_at' => '2026-09-17 13:30:00',
        ]);

        $this->assertSame(
            '01:30',
            $attendanceRecord->total_break_time
        );
    }

    /**
     * 実働時間をHH:MM形式で取得できることを確認する。
     */
    public function test_実働時間を取得できる(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => '2026-09-17 18:00:00',
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-17 12:00:00',
            'break_end_at' => '2026-09-17 13:00:00',
        ]);

        $this->assertSame(
            '08:00',
            $attendanceRecord->total_time
        );
    }

    /**
     * 退勤時刻が未登録の場合は実働時間がnullになることを確認する。
     */
    public function test_退勤時刻が未登録の場合は実働時間がnullになる(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => null,
        ]);

        $this->assertNull(
            $attendanceRecord->total_time
        );
    }
}
