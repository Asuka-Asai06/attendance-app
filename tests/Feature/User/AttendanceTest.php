<?php

namespace Tests\Feature\User;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外のユーザーには出勤ボタンが表示され出勤できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('出勤');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('出勤中')
            ->assertDontSee('>出勤</button>');
    }

    public function test_退勤済みのユーザーには出勤ボタンが表示されない(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(18, 0),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('退勤済')
            ->assertDontSee('>出勤</button>');
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 1, 9, 0, 0)
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in_at' => '2026-09-01 09:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_休憩入ボタンが表示され休憩を開始できる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('休憩入');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ])
            ->assertRedirect('/attendance');

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('休憩戻');

        $this->assertDatabaseHas('break_times', [
            'attendance_record_id' => AttendanceRecord::first()->id,
        ]);
    }

    public function test_休憩戻で出勤中に戻る(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => now()->setTime(12, 0),
            'break_end_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('休憩戻');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ])
            ->assertRedirect('/attendance');

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('出勤中');

        $this->assertDatabaseMissing('break_times', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_end_at' => null,
        ]);
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ])
            ->assertRedirect('/attendance');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ])
            ->assertRedirect('/attendance');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ])
            ->assertRedirect('/attendance');

        $this->assertDatabaseCount('break_times', 2);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('休憩戻');
    }

    public function test_休憩時刻が正しく保存される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 1, 12, 0, 0)
        );

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 1, 9, 0, 0),
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ])
            ->assertRedirect('/attendance');

        Carbon::setTestNow(
            Carbon::create(2026, 9, 1, 13, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('break_times', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-01 12:00:00',
            'break_end_at' => '2026-09-01 13:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_退勤ボタンが表示され退勤できる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('退勤');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ])
            ->assertRedirect('/attendance');

        $this->actingAs($user)
            ->get('/attendance')
            ->assertOk()
            ->assertSee('退勤済');
    }

    public function test_退勤時刻が正しく保存される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 1, 18, 0, 0)
        );

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 1, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_out_at' => '2026-09-01 18:00:00',
        ]);

        Carbon::setTestNow();
    }
}
