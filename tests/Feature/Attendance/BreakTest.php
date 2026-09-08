<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_休憩ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩入');

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩中');
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩入');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $this->assertDatabaseCount('break_times', 2);
    }

    public function test_休憩戻ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩戻');

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $response->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('break_times', [
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();
        $response->assertSee('休憩戻');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $this->assertDatabaseCount('break_times', 2);
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0, 0)
        );

        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('attendance.store'), [
                'action' => 'break_in',
            ]);

        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 13, 0, 0)
        );

        $this->actingAs($user)
            ->post(route('attendance.store'), [
                'action' => 'break_out',
            ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.list'));

        $response->assertOk();
        $response->assertSee('1:00');
    }
}
