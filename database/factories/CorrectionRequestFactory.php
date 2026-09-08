<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrectionRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'user_id' => User::factory(),
            'requested_clock_in_at' => now()->setTime(9, 0),
            'requested_clock_out_at' => now()->setTime(18, 0),
            'comment' => '修正してください。',
            'approval_status' => '承認待ち',
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
