<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffListTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者が全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $admin = User::factory()->create([
            'name' => '管理者',
            'admin_status' => true,
        ]);

        User::factory()->create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'admin_status' => false,
        ]);

        User::factory()->create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'admin_status' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.index'));

        $response->assertOk();

        $response->assertSee('ユーザー1');
        $response->assertSee('user1@example.com');
        $response->assertSee('ユーザー2');
        $response->assertSee('user2@example.com');
    }

    public function test_ユーザーの勤怠情報が正しく表示される(): void
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
            ->get(route('admin.staff.list', ['user' => $user->id]));

        $response->assertOk();

        $response->assertSee('テストユーザー');
        $response->assertSee('09/08');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
    }

    public function test_前月を押下すると前月の情報が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0)
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 8, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 8, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list', ['user' => $user->id]));

        $response->assertOk();
        $response->assertSee('2026/09');

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list', [
                'user' => $user->id,
                'date' => '2026-08-01',
            ]));

        $response->assertOk();

        $response->assertSee('2026/08');
        $response->assertSee('08/08');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_翌月を押下すると翌月の情報が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0)
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 10, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 10, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list', ['user' => $user->id]));

        $response->assertOk();
        $response->assertSee('2026/09');

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list', [
                'user' => $user->id,
                'date' => '2026-10-01',
            ]));

        $response->assertOk();

        $response->assertSee('2026/10');
        $response->assertSee('10/08');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
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

        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list', ['user' => $user->id]));

        $response->assertOk();

        $response->assertSee(
            route('attendance.show', $attendanceRecord)
        );

        $response = $this->actingAs($admin)
            ->get(route('attendance.show', $attendanceRecord));

        $response->assertOk();

        $response->assertSee('テストユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_一般ユーザーはスタッフ一覧を閲覧できない(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.staff.index'));

        $response->assertForbidden();
    }

    public function test_指定したスタッフの指定月の勤怠情報をcsv出力できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 9, 9, 9, 30),
            'clock_out_at' => Carbon::create(2026, 9, 9, 18, 30),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in_at' => Carbon::create(2026, 8, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 8, 8, 18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->post(
                route('admin.staff.export', $user),
                [
                    'year_month' => '2026-09',
                ]
            );

        $response->assertOk();

        $response->assertHeader(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $contentDisposition = $response->headers->get(
            'Content-Disposition'
        );

        $this->assertNotNull($contentDisposition);

        $this->assertStringContainsString(
            'filename*=utf-8\'\'',
            $contentDisposition
        );

        $this->assertStringContainsString(
            rawurlencode('テストユーザー_2026-09_勤怠一覧.csv'),
            $contentDisposition
        );

        $content = $response->streamedContent();

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $content
        );

        $this->assertStringContainsString(
            '日付,出勤,退勤,休憩,合計',
            $content
        );

        $this->assertStringContainsString(
            '09/08,09:00,18:00',
            $content
        );

        $this->assertStringContainsString(
            '09/09,09:30,18:30',
            $content
        );

        $this->assertStringNotContainsString(
            '08/08',
            $content
        );
    }
}
