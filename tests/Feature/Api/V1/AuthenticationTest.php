<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証時に登録しようとすると401が返る(): void
    {
        $response = $this->postJson(
            route('api.v1.attendance-records.store'),
            [
                'date' => '2026-09-17',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => 'テスト',
            ]
        );

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_未認証時に更新しようとすると401が返る(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'date' => '2026-09-17',
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => '2026-09-17 18:00:00',
        ]);

        $response = $this->putJson(
            route(
                'api.v1.attendance-records.update',
                $attendanceRecord
            ),
            [
                'date' => '2026-09-17',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '更新テスト',
            ]
        );

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => '2026-09-17 18:00:00',
        ]);
    }

    public function test_未認証時に削除しようとすると401が返る(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->deleteJson(
            route('api.v1.attendance-records.destroy', $attendanceRecord)
        );

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    public function test_認証済みユーザーは自分の勤怠を更新できる(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-17',
            'clock_in_at' => '2026-09-17 09:00:00',
            'clock_out_at' => '2026-09-17 18:00:00',
        ]);

        $response = $this->putJson(
            route(
                'api.v1.attendance-records.update',
                $attendanceRecord
            ),
            [
                'date' => '2026-09-17',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '更新テスト',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'clock_in_at' => '2026-09-17 10:00:00',
            'clock_out_at' => '2026-09-17 19:00:00',
            'comment' => '更新テスト',
        ]);
    }

    public function test_認証済みユーザーは自分の勤怠を削除できる(): void
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

    public function test_他ユーザーの勤怠を更新しようとすると403が返る(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson(
            route(
                'api.v1.attendance-records.update',
                $attendanceRecord
            ),
            [
                'date' => '2026-09-17',
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
                'comment' => '更新テスト',
            ]
        );

        $response
            ->assertStatus(403)
            ->assertJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_他ユーザーの勤怠を削除しようとすると403が返る(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson(
            route(
                'api.v1.attendance-records.destroy',
                $attendanceRecord
            )
        );

        $response
            ->assertStatus(403)
            ->assertJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    public function test_ログインできる(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
            ]);
    }

    public function test_ログインするとトークンが発行される(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
            ]);

        $this->assertCount(1, $user->fresh()->tokens);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_メールアドレス未入力ではログインできない(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_メールアドレス形式が不正ではログインできない(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'abc',
            'password' => 'password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_パスワード未入力ではログインできない(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'password',
            ]);
    }

    public function test_認証情報が間違っているとログインできない(): void
    {
        User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'ログイン情報が登録されていません。',
            ]);
    }
}
