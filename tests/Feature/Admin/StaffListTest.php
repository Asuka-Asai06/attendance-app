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
}
