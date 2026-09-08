<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('出勤');

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('出勤中');
    }

    public function test_出勤は一日一回のみできる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();

        $response->assertDontSee('出勤');
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 13, 0, 0)
        );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in_at' => '2026-09-08 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('13:00');
    }
}
