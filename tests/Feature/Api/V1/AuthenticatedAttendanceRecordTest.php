<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticatedAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠を作成できる(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'date' => '2026-09-17',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'テストコメント',
        ];

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            $data
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-09-17',
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => '2026-09-17 18:00:00',
            'comment' => 'テストコメント',
        ]);
    }

    public function test_必須項目がない場合は422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            []
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date',
                'clock_in',
            ])
            ->assertJson([
                'errors' => [
                    'date' => [
                        '勤怠日は必須です。',
                    ],
                    'clock_in' => [
                        '出勤時刻は必須です。',
                    ],
                ],
            ]);
    }

    public function test_日付と時刻の形式が不正な場合は422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026/09/17',
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date',
                'clock_in',
                'clock_out',
            ])
            ->assertJson([
                'errors' => [
                    'date' => [
                        '勤怠日は YYYY-MM-DD 形式で指定してください。',
                    ],
                    'clock_in' => [
                        '出勤時刻は HH:MM:SS 形式で指定してください。',
                    ],
                    'clock_out' => [
                        '退勤時刻は HH:MM:SS 形式で指定してください。',
                    ],
                ],
            ]);
    }

    public function test_退勤時刻が出勤時刻より前の場合は422が返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '18:00:00',
                'clock_out' => '09:00:00',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'clock_out',
            ])
            ->assertJson([
                'errors' => [
                    'clock_out' => [
                        '退勤時刻は出勤時刻より後の時刻を指定してください。',
                    ],
                ],
            ]);
    }

    public function test_休憩時間の形式が不正な場合は422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'breaks' => [
                    [
                        'break_in' => '12:00',
                        'break_out' => '13:00',
                    ],
                ],
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'breaks.0.break_in',
                'breaks.0.break_out',
            ])
            ->assertJson([
                'errors' => [
                    'breaks.0.break_in' => [
                        '休憩開始時刻はHH:MM:SS形式で入力してください。',
                    ],
                    'breaks.0.break_out' => [
                        '休憩終了時刻はHH:MM:SS形式で入力してください。',
                    ],
                ],
            ]);
    }

    public function test_備考が255文字を超える場合は422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => str_repeat('あ', 256),
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'comment',
            ])
            ->assertJson([
                'errors' => [
                    'comment' => [
                        '備考は 255 文字以内で入力してください。',
                    ],
                ],
            ]);
    }

    public function test_同じ日付の勤怠が既に存在する場合は422と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-17',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'date',
            ])
            ->assertJson([
                'errors' => [
                    'date' => [
                        'この日付の勤怠は既に登録されています。',
                    ],
                ],
            ]);
    }

    public function test_別ユーザーなら同じ日付の勤怠を登録できる(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-17',
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]
        );

        $response->assertStatus(201);
    }

    public function test_勤怠を削除できる(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(
            route(
                'api.v1.attendance-records.destroy',
                $attendanceRecord
            )
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    public function test_存在しない勤怠idを削除しようとすると404が返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            route(
                'api.v1.attendance-records.destroy',
                99999
            )
        );

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_認証済みユーザーが登録すると自分の勤怠として保存される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => 'テスト',
            ]
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-09-17',
        ]);
    }
}
