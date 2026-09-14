<?php

namespace Tests\Feature\User;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ゲストはレポートページにアクセスできない(): void
    {
        $response = $this->get(route('attendance.report'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証ユーザーの統計情報が正しく計算される(): void
    {
        $user = User::factory()->create();

        $currentMonth = now()->startOfMonth();

        $attendanceRecord1 = AttendanceRecord::create([
            'user_id' => $user->id,
            'clock_in_at' => $currentMonth->copy()->setDay(1)->setTime(9, 0),
            'clock_out_at' => $currentMonth->copy()->setDay(1)->setTime(18, 0),
        ]);

        BreakTime::create([
            'attendance_record_id' => $attendanceRecord1->id,
            'break_start_at' => $currentMonth->copy()->setDay(1)->setTime(12, 0),
            'break_end_at' => $currentMonth->copy()->setDay(1)->setTime(13, 0),
        ]);

        $attendanceRecord2 = AttendanceRecord::create([
            'user_id' => $user->id,
            'clock_in_at' => $currentMonth->copy()->setDay(2)->setTime(9, 30),
            'clock_out_at' => $currentMonth->copy()->setDay(2)->setTime(18, 0),
        ]);

        BreakTime::create([
            'attendance_record_id' => $attendanceRecord2->id,
            'break_start_at' => $currentMonth->copy()->setDay(2)->setTime(12, 0),
            'break_end_at' => $currentMonth->copy()->setDay(2)->setTime(13, 0),
        ]);

        $attendanceRecord3 = AttendanceRecord::create([
            'user_id' => $user->id,
            'clock_in_at' => $currentMonth->copy()->setDay(3)->setTime(9, 0),
            'clock_out_at' => $currentMonth->copy()->setDay(3)->setTime(20, 0),
        ]);

        BreakTime::create([
            'attendance_record_id' => $attendanceRecord3->id,
            'break_start_at' => $currentMonth->copy()->setDay(3)->setTime(12, 0),
            'break_end_at' => $currentMonth->copy()->setDay(3)->setTime(13, 0),
        ]);

        $response = $this->actingAs($user)->get(
            route('attendance.report')
        );

        $response->assertOk();

        $response->assertViewHas('summary', function (array $summary): bool {
            return $summary['total_work_minutes'] === 1530
                && $summary['total_overtime_minutes'] === 120
                && $summary['avg_work_minutes'] === 510;
        });

        $response->assertViewHas('monthlyTrend', function (array $monthlyTrend): bool {
            $currentMonth = now()->format('Y/m');

            $currentMonthData = collect($monthlyTrend)
                ->firstWhere('month', $currentMonth);

            return $currentMonthData !== null
                && $currentMonthData['work_minutes'] === 1530
                && $currentMonthData['overtime_minutes'] === 120;
        });

        $response->assertViewHas('anomalies', function (array $anomalies): bool {
            return $anomalies['late_count'] === 1
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }

    public function test_勤怠記録がないユーザーでも安全にレポートを表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(
            route('attendance.report')
        );

        $response->assertOk();

        $response->assertViewHas('summary', function (array $summary): bool {
            return $summary['total_work_minutes'] === 0
                && $summary['total_overtime_minutes'] === 0
                && $summary['avg_work_minutes'] === 0;
        });

        $response->assertViewHas('monthlyTrend', function (array $monthlyTrend): bool {
            return count($monthlyTrend) === 6
                && collect($monthlyTrend)->every(
                    function (array $month): bool {
                        return $month['work_minutes'] === 0
                            && $month['overtime_minutes'] === 0;
                    }
                );
        });

        $response->assertViewHas('anomalies', function (array $anomalies): bool {
            return $anomalies['late_count'] === 0
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }

    public function test_他ユーザーの勤怠と混ざらない(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $currentMonth = now()->startOfMonth();

        $user1Attendance = AttendanceRecord::create([
            'user_id' => $user1->id,
            'clock_in_at' => $currentMonth->copy()
                ->setDay(1)
                ->setTime(9, 0),
            'clock_out_at' => $currentMonth->copy()
                ->setDay(1)
                ->setTime(18, 0),
        ]);

        BreakTime::create([
            'attendance_record_id' => $user1Attendance->id,
            'break_start_at' => $currentMonth->copy()
                ->setDay(1)
                ->setTime(12, 0),
            'break_end_at' => $currentMonth->copy()
                ->setDay(1)
                ->setTime(13, 0),
        ]);

        $user2Attendance = AttendanceRecord::create([
            'user_id' => $user2->id,
            'clock_in_at' => $currentMonth->copy()
                ->setDay(2)
                ->setTime(9, 0),
            'clock_out_at' => $currentMonth->copy()
                ->setDay(2)
                ->setTime(20, 0),
        ]);

        BreakTime::create([
            'attendance_record_id' => $user2Attendance->id,
            'break_start_at' => $currentMonth->copy()
                ->setDay(2)
                ->setTime(12, 0),
            'break_end_at' => $currentMonth->copy()
                ->setDay(2)
                ->setTime(13, 0),
        ]);

        $response = $this->actingAs($user1)->get(
            route('attendance.report')
        );

        $response->assertOk();

        $response->assertViewHas('summary', function (array $summary): bool {
            return $summary['total_work_minutes'] === 480
                && $summary['total_overtime_minutes'] === 0
                && $summary['avg_work_minutes'] === 480;
        });

        $response->assertViewHas('monthlyTrend', function (array $monthlyTrend): bool {
            $currentMonth = now()->format('Y/m');

            $currentMonthData = collect($monthlyTrend)
                ->firstWhere('month', $currentMonth);

            return $currentMonthData !== null
                && $currentMonthData['work_minutes'] === 480
                && $currentMonthData['overtime_minutes'] === 0;
        });

        $response->assertViewHas('anomalies', function (array $anomalies): bool {
            return $anomalies['late_count'] === 0
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }
}
