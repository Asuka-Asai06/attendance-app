<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now();

        return [
            'user_id' => User::factory(),
            'date' => $date->toDateString(),
            'clock_in_at' => $date->copy()->setTime(9, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0),
        ];

    }
}
