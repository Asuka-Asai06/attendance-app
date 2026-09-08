<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合は表示されているステータスが勤務外となる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合は表示されているステータスが出勤中となる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合は表示されているステータスが休憩中となる(): void
    {
        $user = User::factory()->create();

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendance->id,
            'break_start_at' => now()->setTime(12, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合は表示されているステータスが退勤済となる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(17, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();

        $response->assertSee('退勤済');
    }
}
