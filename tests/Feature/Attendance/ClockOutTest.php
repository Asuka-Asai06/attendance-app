<?php

namespace Tests\Feature\Attendance;

use App\Actions\AttendanceAction;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_退勤ボタンが正しく機能する(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 9, 0, 0)
        );

        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('退勤');

        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 18, 0, 0)
        );

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_out_at' => '2026-09-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('退勤済');
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 9, 0, 0)
        );

        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('退勤');

        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 18, 0, 0)
        );

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_out_at' => '2026-09-08 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('18:00');
    }

    public function test_退勤済みの場合は再度退勤できない(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(18, 0),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('すでに退勤しています');

        $action = app(AttendanceAction::class);

        $action->clockOut($user);
    }

    public function test_出勤していない場合は退勤できない(): void
    {
        $user = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('出勤していません');

        $action = app(AttendanceAction::class);

        $action->clockOut($user);
    }

    public function test_休憩中は退勤できない(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => now()->setTime(12, 0),
            'break_end_at' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('休憩中は退勤できません');

        $action = app(AttendanceAction::class);

        $action->clockOut($user);
    }
}
