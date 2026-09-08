<?php

namespace Tests\Feature\User;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(
                '/attendance/'.$attendanceRecord->id
            );

        $response->assertOk();

        $response->assertSee('テストユーザー');
    }

    public function test_勤怠詳細画面の日付が選択した日付になっている(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8)
        );

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/'.$attendanceRecord->id);

        $response->assertOk();

        $response->assertSee('09月05日');
    }

    public function test_出勤退勤時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/'.$attendanceRecord->id);

        $response->assertOk();

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_休憩時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => Carbon::create(2026, 9, 5, 12, 0),
            'break_end_at' => Carbon::create(2026, 9, 5, 13, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => Carbon::create(2026, 9, 5, 15, 0),
            'break_end_at' => Carbon::create(2026, 9, 5, 15, 15),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/'.$attendanceRecord->id);

        $response->assertOk();

        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
    }
}
