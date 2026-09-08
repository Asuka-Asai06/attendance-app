<?php

namespace Tests\Feature\User;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分の勤怠情報が全て表示されている(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 13, 0, 0)
        );

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 1, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 1, 18, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_start_at' => Carbon::create(2026, 9, 1, 12, 0),
            'break_end_at' => Carbon::create(2026, 9, 1, 13, 0),
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 2, 9, 30),
            'clock_out_at' => Carbon::create(2026, 9, 2, 18, 30),
        ]);

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord2->id,
            'break_start_at' => Carbon::create(2026, 9, 2, 12, 30),
            'break_end_at' => Carbon::create(2026, 9, 2, 13, 30),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'clock_in_at' => Carbon::create(2026, 9, 3, 10, 0),
            'clock_out_at' => Carbon::create(2026, 9, 3, 19, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();

        $response->assertSee('09/01');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');

        $response->assertSee('09/02');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('1:00');

        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8)
        );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();

        $response->assertSee('2026/09');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8)
        );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('2026/09');

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-08');

        $response->assertOk();
        $response->assertSee('2026/08');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8)
        );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('2026/09');

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-10');

        $response->assertOk();
        $response->assertSee('2026/10');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => now()->setTime(9, 0),
            'clock_out_at' => now()->setTime(18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertOk();

        $response->assertSee(
            '/attendance/'.$attendanceRecord->id
        );

        $response = $this->actingAs($user)
            ->get(
                '/attendance/'.$attendanceRecord->id
            );

        $response->assertOk();

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
