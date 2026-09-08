<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => Carbon::create(2026, 9, 8, 12, 0),
            'break_end_at' => Carbon::create(2026, 9, 8, 13, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('attendance.show', $attendanceRecord));

        $response->assertOk();

        $response->assertSee('テストユーザー');
        $response->assertSee('2026年');
        $response->assertSee('09月08日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_出勤時間が退勤時間より後の場合はエラーメッセージが表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('attendance.show', $attendanceRecord))
            ->post(
                route('attendance.correction.store', $attendanceRecord),
                [
                    'new_clock_in' => '19:00',
                    'new_clock_out' => '18:00',
                    'new_break_in' => [],
                    'new_break_out' => [],
                    'comment' => '出勤時間を修正します。',
                ]
            );

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後の場合はエラーメッセージが表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('attendance.show', $attendanceRecord))
            ->post(
                route('attendance.correction.store', $attendanceRecord),
                [
                    'new_clock_in' => '09:00',
                    'new_clock_out' => '18:00',
                    'new_break_in' => [
                        '19:00',
                    ],
                    'new_break_out' => [
                        '19:30',
                    ],
                    'comment' => '休憩時間を修正します。',
                ]
            );

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後の場合はエラーメッセージが表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('attendance.show', $attendanceRecord))
            ->post(
                route('attendance.correction.store', $attendanceRecord),
                [
                    'new_clock_in' => '09:00',
                    'new_clock_out' => '18:00',
                    'new_break_in' => [
                        '12:00',
                    ],
                    'new_break_out' => [
                        '19:00',
                    ],
                    'comment' => '休憩時間を修正します。',
                ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合はエラーメッセージが表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('attendance.show', $attendanceRecord))
            ->post(
                route('attendance.correction.store', $attendanceRecord), [
                    'new_clock_in' => '09:00',
                    'new_clock_out' => '18:00',
                    'new_break_in' => [],
                    'new_break_out' => [],
                    'comment' => '',
                ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }
}
