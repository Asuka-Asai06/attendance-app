<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakTime>
 */
class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'break_start_at' => now()->setTime(13, 0),
            'break_end_at' => now()->setTime(14, 0),
        ];
    }
}
