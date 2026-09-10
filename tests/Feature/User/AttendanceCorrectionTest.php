<?php

namespace Tests\Feature\User;

use App\Models\AttendanceRecord;
use App\Models\CorrectionRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後になっている場合はエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance/'.$attendanceRecord->id)
            ->post('/attendance/'.$attendanceRecord->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [],
                'new_break_out' => [],
                'comment' => '出勤時間を修正します。',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合はエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance/'.$attendanceRecord->id)
            ->post('/attendance/'.$attendanceRecord->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [
                    '19:00',
                ],
                'new_break_out' => [
                    '19:30',
                ],
                'comment' => '休憩時間を修正します。',
            ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合はエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance/'.$attendanceRecord->id)
            ->post('/attendance/'.$attendanceRecord->id, [
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
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->from('/attendance/'.$attendanceRecord->id)
            ->post('/attendance/'.$attendanceRecord->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [
                    '12:00',
                ],
                'new_break_out' => [
                    '13:00',
                ],
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    public function test_修正申請処理が実行される(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->post('/attendance/'.$attendanceRecord->id, [
                'new_clock_in' => '09:30',
                'new_clock_out' => '18:30',
                'new_break_in' => [
                    '12:00',
                ],
                'new_break_out' => [
                    '13:00',
                ],
                'comment' => '出勤時間と退勤時間を修正してください。',
            ]);

        $response->assertRedirect(
            route('correction.request.list')
        );

        $this->assertDatabaseHas('correction_requests', [
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => '2026-09-08 09:30:00',
            'requested_clock_out_at' => '2026-09-08 18:30:00',
            'comment' => '出勤時間と退勤時間を修正してください。',
            'approval_status' => '承認待ち',
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertOk();
        $response->assertSee('テストユーザー');
        $response->assertSee('承認待ち');
        $response->assertSee('出勤時間と退勤時間を修正してください。');

        $correctionRequest = CorrectionRequest::query()
            ->where('attendance_record_id', $attendanceRecord->id)
            ->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(
                '/stamp_correction_request/approve/'.
                $correctionRequest->id
            );

        $response->assertOk();
        $response->assertSee('テストユーザー');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('出勤時間と退勤時間を修正してください。');
    }

    public function test_承認待ちにログインユーザーが行った申請が全て表示されている(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 6, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 6, 18, 0),
        ]);

        $this->actingAs($user)
            ->post('/attendance/'.$attendanceRecord1->id, [
                'new_clock_in' => '09:30',
                'new_clock_out' => '18:30',
                'new_break_in' => [
                    '12:00',
                ],
                'new_break_out' => [
                    '13:00',
                ],
                'comment' => '1件目の修正申請です。',
            ]);

        $this->actingAs($user)
            ->post('/attendance/'.$attendanceRecord2->id, [
                'new_clock_in' => '10:00',
                'new_clock_out' => '19:00',
                'new_break_in' => [
                    '12:30',
                ],
                'new_break_out' => [
                    '13:30',
                ],
                'comment' => '2件目の修正申請です。',
            ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertOk();

        $response->assertSee('承認待ち');
        $response->assertSee('1件目の修正申請です。');
        $response->assertSee('2件目の修正申請です。');

        $response->assertSee('2026/09/05');
        $response->assertSee('2026/09/06');
    }

    public function test_承認済みに管理者が承認した修正申請が全て表示されている(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 5, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 5, 18, 0),
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 6, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 6, 18, 0),
        ]);

        $this->actingAs($user)
            ->post('/attendance/'.$attendanceRecord1->id, [
                'new_clock_in' => '09:30',
                'new_clock_out' => '18:30',
                'new_break_in' => [
                    '12:00',
                ],
                'new_break_out' => [
                    '13:00',
                ],
                'comment' => '1件目の修正申請です。',
            ]);

        $this->actingAs($user)
            ->post('/attendance/'.$attendanceRecord2->id, [
                'new_clock_in' => '10:00',
                'new_clock_out' => '19:00',
                'new_break_in' => [
                    '12:30',
                ],
                'new_break_out' => [
                    '13:30',
                ],
                'comment' => '2件目の修正申請です。',
            ]);

        $correctionRequest1 = CorrectionRequest::query()
            ->where('attendance_record_id', $attendanceRecord1->id)
            ->firstOrFail();

        $correctionRequest2 = CorrectionRequest::query()
            ->where('attendance_record_id', $attendanceRecord2->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(
                '/stamp_correction_request/approve/'.
                $correctionRequest1->id
            );

        $this->actingAs($admin)
            ->post(
                '/stamp_correction_request/approve/'.
                $correctionRequest2->id
            );

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertOk();

        $response->assertSee('承認済み');
        $response->assertSee('1件目の修正申請です。');
        $response->assertSee('2件目の修正申請です。');

        $response->assertSee('2026/09/05');
        $response->assertSee('2026/09/06');
    }

    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $this->actingAs($user)
            ->post('/attendance/'.$attendanceRecord->id, [
                'new_clock_in' => '09:30',
                'new_clock_out' => '18:30',
                'new_break_in' => [
                    '12:00',
                ],
                'new_break_out' => [
                    '13:00',
                ],
                'comment' => '勤怠を修正してください。',
            ]);

        $correctionRequest = CorrectionRequest::query()
            ->where('attendance_record_id', $attendanceRecord->id)
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertOk();

        $response->assertSee(
            '/stamp_correction_request/approve/'.$correctionRequest->id
        );

        $response = $this->actingAs($user)
            ->get(
                '/stamp_correction_request/approve/'.
                $correctionRequest->id
            );

        $response->assertOk();
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_他人の勤怠詳細画面にはアクセスできない(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.show', $attendanceRecord));

        $response->assertForbidden();
    }

    public function test_他人の勤怠を修正できない(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $response = $this->actingAs($user)
            ->post(
                route('attendance.correction.store', $attendanceRecord),
                [
                    'new_clock_in' => '09:30',
                    'new_clock_out' => '18:30',
                    'new_break_in' => [],
                    'new_break_out' => [],
                    'comment' => '他人の勤怠を修正しようとしています。',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $otherUser->id,
            'clock_in_at' => '2026-09-08 09:00:00',
            'clock_out_at' => '2026-09-08 18:00:00',
        ]);
    }

    public function test_他人の修正申請詳細を閲覧できない(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $otherUser->id,
            'attendance_record_id' => $attendanceRecord->id,
            'approval_status' => '承認待ち',
        ]);

        $response = $this->actingAs($user)
            ->get(route(
                'correction.request.show',
                $correctionRequest
            ));

        $response->assertForbidden();
    }
}
