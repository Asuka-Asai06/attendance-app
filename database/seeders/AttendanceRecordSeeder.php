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
        $this->createUser2AttendanceRecords($user2);
        $this->createUser3AttendanceRecords($user3);
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
                    'date' => $date->toDateString(),
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
                'date' => $date->toDateString(),
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
     * user2の勤怠データを作成
     */
    private function createUser2AttendanceRecords(User $user): void
    {
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $month = now()->subMonths($monthOffset);
            $weekdays = $this->getWeekdays($month);

            foreach (array_slice($weekdays, 0, 15) as $index => $date) {
                $pattern = $this->getUser2Pattern($index);

                $this->createAttendanceRecord($user, $date, $pattern);
            }
        }

        $currentMonthWeekdays = $this->getPastWeekdays(now());

        foreach ($currentMonthWeekdays as $index => $date) {
            $pattern = $this->getUser2Pattern($index);

            $this->createAttendanceRecord($user, $date, $pattern);
        }
    }

    /**
     * user3（管理者）の勤怠データを作成
     */
    private function createUser3AttendanceRecords(User $user): void
    {
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $month = now()->subMonths($monthOffset);
            $weekdays = $this->getWeekdays($month);

            foreach (array_slice($weekdays, 0, 15) as $index => $date) {
                $pattern = $this->getUser3Pattern($index);

                $this->createAttendanceRecord($user, $date, $pattern);
            }
        }

        $currentMonthWeekdays = $this->getPastWeekdays(now());

        foreach ($currentMonthWeekdays as $index => $date) {
            $pattern = $this->getUser3Pattern($index);

            $this->createAttendanceRecord($user, $date, $pattern);
        }
    }

    /**
     * user2用の勤務パターンを返す
     *
     * @return array{clock_in: string, clock_out: string}
     */
    private function getUser2Pattern(int $index): array
    {
        $patterns = [
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:10',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '17:30',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '19:00',
            ],
            [
                'clock_in' => '08:50',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:15',
                'clock_out' => '18:30',
            ],
        ];

        return $patterns[$index % count($patterns)];
    }

    /**
     * user3（管理者）用の勤務パターンを返す
     *
     * @return array{clock_in: string, clock_out: string}
     */
    private function getUser3Pattern(int $index): array
    {
        $patterns = [
            [
                'clock_in' => '08:45',
                'clock_out' => '17:45',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:20',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '19:30',
            ],
            [
                'clock_in' => '08:30',
                'clock_out' => '18:00',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '17:30',
            ],
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ],
        ];

        return $patterns[$index % count($patterns)];
    }

    /**
     * 勤怠記録を1件作成する
     *
     * @param  array{clock_in: string, clock_out: string}  $pattern
     */
    private function createAttendanceRecord(User $user, Carbon $date, array $pattern): void
    {
        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'clock_in_at' => $date
                ->copy()
                ->setTimeFromTimeString($pattern['clock_in']),
            'clock_out_at' => $date
                ->copy()
                ->setTimeFromTimeString($pattern['clock_out']),
        ]);
    }

    /**
     * 指定月の平日を取得する
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

    /**
     * 今日を除いた現在月の平日を取得する
     *
     * @param  Carbon  $month  対象月
     * @return array<int, Carbon> 前日までの平日の一覧
     */
    private function getPastWeekdays(Carbon $month): array
    {
        $today = today();

        return array_values(
            array_filter(
                $this->getWeekdays($month),
                function (Carbon $date) use ($today): bool {
                    return $date->lt($today);
                }
            )
        );
    }
}
