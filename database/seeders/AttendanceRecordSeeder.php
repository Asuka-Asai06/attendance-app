<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    /**
     * 勤怠記録のダミーデータを作成する。
     */
    public function run(): void
    {
        $user1 = User::query()
            ->where('email', 'user1@example.com')
            ->firstOrFail();

        $user2 = User::query()
            ->where('email', 'user2@example.com')
            ->firstOrFail();

        $user3 = User::query()
            ->where('email', 'user3@example.com')
            ->firstOrFail();

        $this->createUser1AttendanceRecords($user1);
        $this->createDummyAttendanceRecords($user2);
    }

    /**
     * user1の勤怠データを作成
     */
    private function createUser1AttendanceRecords(User $user): void
    {
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $month = now()->subMonths($monthOffset);

            $weekdays = $this->getWeekdays($month);

            foreach (array_slice($weekdays, 0, 15) as $date) {
                AttendanceRecord::create([
                    'user_id' => $user->id,
                    'clock_in_at' => $date->copy()->setTime(9, 0),
                    'clock_out_at' => $date->copy()->setTime(18, 0),
                ]);
            }
        }

        $currentMonthWeekdays = $this->getWeekdays(now());

        $patterns = [
            ...array_fill(0, 10, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ]),

            ...array_fill(0, 3, [
                'clock_in' => '09:00',
                'clock_out' => '20:00',
            ]),

            ...array_fill(0, 2, [
                'clock_in' => '09:30',
                'clock_out' => '18:00',
            ]),

            [
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ],

            [
                'clock_in' => '08:00',
                'clock_out' => '21:00',
            ],
        ];

        foreach ($patterns as $index => $pattern) {
            $date = $currentMonthWeekdays[$index];

            AttendanceRecord::create([
                'user_id' => $user->id,
                'clock_in_at' => $date
                    ->copy()
                    ->setTimeFromTimeString($pattern['clock_in']),
                'clock_out_at' => $date
                    ->copy()
                    ->setTimeFromTimeString($pattern['clock_out']),
            ]);
        }
    }

    /**
     * user2用の勤怠ダミーデータを作成する
     */
    private function createDummyAttendanceRecords(User $user): void
    {
        for ($monthOffset = 6; $monthOffset >= 1; $monthOffset--) {
            $month = now()->subMonths($monthOffset);

            $weekdays = $this->getWeekdays($month);

            foreach (array_slice($weekdays, 0, 10) as $date) {
                AttendanceRecord::create([
                    'user_id' => $user->id,
                    'clock_in_at' => $date->copy()->setTime(9, 0),
                    'clock_out_at' => $date->copy()->setTime(18, 0),
                ]);
            }
        }

        $currentMonthWeekdays = $this->getWeekdays(now());

        foreach (array_slice($currentMonthWeekdays, 0, 10) as $date) {
            AttendanceRecord::create([
                'user_id' => $user->id,
                'clock_in_at' => $date->copy()->setTime(9, 0),
                'clock_out_at' => $date->copy()->setTime(18, 0),
            ]);
        }
    }

    /**
     * 指定月の平日を取得する。
     *
     * @param  Carbon  $month  対象月
     * @return array<int, Carbon> 平日の一覧
     */
    private function getWeekdays(Carbon $month): array
    {
        $date = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $weekdays = [];

        while ($date->lte($endOfMonth)) {
            if ($date->isWeekday()) {
                $weekdays[] = $date->copy();
            }

            $date->addDay();
        }

        return $weekdays;
    }
}
