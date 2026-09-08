<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者勤怠一覧にその日の全ユーザーの勤怠情報が正確に表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0)
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
        ]);

        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 9, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 18, 0),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'clock_in_at' => Carbon::create(2026, 9, 8, 10, 0),
            'clock_out_at' => Carbon::create(2026, 9, 8, 19, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list'));

        $response->assertOk();

        $response->assertSee('2026年09月08日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_管理者勤怠一覧に現在の日付が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0)
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list'));

        $response->assertOk();

        $response->assertSee('2026年09月08日');
    }

    public function test_前日を押下すると前日の勤怠情報が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0)
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list'));

        $response->assertOk();

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list', [
                'date' => '2026-09-07',
            ]));

        $response->assertOk();

        $response->assertSee('2026年09月07日');
    }

    public function test_翌日を押下すると翌日の勤怠情報が表示される(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 12, 0)
        );

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list'));

        $response->assertOk();

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.list', [
                'date' => '2026-09-09',
            ]));

        $response->assertOk();

        $response->assertSee('2026年09月09日');
    }

    public function test_一般ユーザーは管理者勤怠一覧を閲覧できない(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.attendance.list'));

        $response->assertForbidden();
    }
}
