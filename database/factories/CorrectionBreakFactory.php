<?php

namespace Database\Factories;

use App\Models\CorrectionBreak;
use App\Models\CorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionBreak>
 */
class CorrectionBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'correction_request_id' => CorrectionRequest::factory(),
            'break_start_at' => now()->setTime(13, 0),
            'break_end_at' => now()->setTime(14, 0),
        ];
    }
}
