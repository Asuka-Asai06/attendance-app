<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\CorrectionBreak;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠一覧をjsonで取得できる(): void
    {
        $this->seed();

        $response = $this->getJson(
            route('api.v1.attendance-records.index')
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'date',
                        'clock_in',
                        'clock_out',
                        'total_time',
                        'total_break_time',
                        'comment',
                        'breaks' => [
                            '*' => [
                                'id',
                                'break_in',
                                'break_out',
                            ],
                        ],
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);
    }

    public function test_勤怠詳細をjsonで取得できる(): void
    {
        $this->seed();

        $attendanceRecord = AttendanceRecord::first();

        $response = $this->getJson(
            route(
                'api.v1.attendance-records.show',
                $attendanceRecord
            )
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'name',
                    ],
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'breaks' => [
                        '*' => [
                            'id',
                            'break_in',
                            'break_out',
                        ],
                    ],
                    'applications' => [],
                ],
            ]);
    }

    public function test_存在しない勤怠idを指定すると404とエラーjsonが返る(): void
    {
        $response = $this->getJson(
            route(
                'api.v1.attendance-records.show',
                99999
            )
        );

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_勤怠詳細に修正申請と休憩時間が含まれる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-17',
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => '2026-09-17 18:00:00',
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'requested_clock_in_at' => '2026-09-17 10:00:00',
            'requested_clock_out_at' => '2026-09-17 19:00:00',
            'comment' => '修正申請テスト',
        ]);

        CorrectionBreak::factory()->create([
            'correction_request_id' => $correctionRequest->id,
            'break_start_at' => '2026-09-17 12:00:00',
            'break_end_at' => '2026-09-17 13:00:00',
        ]);

        $response = $this->getJson(
            route(
                'api.v1.attendance-records.show',
                $attendanceRecord
            )
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user',
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'breaks',
                    'applications' => [
                        '*' => [
                            'id',
                            'requested_clock_in_at',
                            'requested_clock_out_at',
                            'comment',
                            'approval_status',
                            'approved_at',
                            'breaks' => [
                                '*' => [
                                    'id',
                                    'break_in',
                                    'break_out',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
