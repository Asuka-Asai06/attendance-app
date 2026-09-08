<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\CorrectionBreak;
use App\Models\CorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_承認待ちの修正申請が全て表示されている(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
        ]);

        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
        ]);

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'clock_in_at' => Carbon::create(2026, 9, 6, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 6, 18, 0),
        ]);

        CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'user_id' => $user1->id,
            'approval_status' => '承認待ち',
            'comment' => '1件目の修正申請です。',
        ]);

        CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord2->id,
            'user_id' => $user2->id,
            'approval_status' => '承認待ち',
            'comment' => '2件目の修正申請です。',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('correction.request.list'));

        $response->assertOk();

        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');
        $response->assertSee('1件目の修正申請です。');
        $response->assertSee('2件目の修正申請です。');
        $response->assertSee('承認待ち');
    }

    public function test_承認済みの修正申請が全て表示されている(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
        ]);

        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
        ]);

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'clock_in_at' => Carbon::create(2026, 9, 6, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 6, 18, 0),
        ]);

        CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord1->id,
            'user_id' => $user1->id,
            'approval_status' => '承認済み',
            'comment' => '1件目の承認済み申請です。',
            'approved_by' => $admin->id,
            'approved_at' => Carbon::create(2026, 9, 8, 10, 0),
        ]);

        CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord2->id,
            'user_id' => $user2->id,
            'approval_status' => '承認済み',
            'comment' => '2件目の承認済み申請です。',
            'approved_by' => $admin->id,
            'approved_at' => Carbon::create(2026, 9, 8, 11, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('correction.request.list'));

        $response->assertOk();

        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');
        $response->assertSee('1件目の承認済み申請です。');
        $response->assertSee('2件目の承認済み申請です。');
        $response->assertSee('承認済み');
    }

    public function test_修正申請の詳細内容が正しく表示されている(): void
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

        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 9, 8, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 9, 8, 18, 30),
            'comment' => '出退勤時間を修正してください。',
            'approval_status' => '承認待ち',
        ]);

        CorrectionBreak::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_start_at' => Carbon::create(2026, 9, 8, 12, 0),
            'break_end_at' => Carbon::create(2026, 9, 8, 13, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route(
                'correction.request.show',
                $correctionRequest
            ));

        $response->assertOk();

        $response->assertSee('テストユーザー');
        $response->assertSee('9月8日');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('出退勤時間を修正してください。');
        $response->assertSee('承認');
    }

    public function test_修正申請の承認処理が正しく行われ勤怠情報が更新される(): void
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

        BreakTime::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => Carbon::create(2026, 9, 8, 12, 0),
            'break_end_at' => Carbon::create(2026, 9, 8, 13, 0),
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 9, 8, 9, 30),
            'requested_clock_out_at' => Carbon::create(2026, 9, 8, 18, 30),
            'comment' => '出退勤時間を修正してください。',
            'approval_status' => '承認待ち',
        ]);

        CorrectionBreak::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_start_at' => Carbon::create(2026, 9, 8, 12, 30),
            'break_end_at' => Carbon::create(2026, 9, 8, 13, 30),
        ]);

        $response = $this->actingAs($admin)
            ->post(
                route(
                    'correction.request.approve',
                    $correctionRequest
                )
            );

        $response->assertRedirect(
            route('correction.request.list')
        );

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'approval_status' => '承認済み',
            'approved_by' => $admin->id,
        ]);

        $this->assertNotNull(
            CorrectionRequest::find($correctionRequest->id)->approved_at
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in_at' => '2026-09-08 09:30:00',
            'clock_out_at' => '2026-09-08 18:30:00',
        ]);

        $this->assertDatabaseHas('break_times', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_start_at' => '2026-09-08 12:30:00',
            'break_end_at' => '2026-09-08 13:30:00',
        ]);
    }
}
