<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentDateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報がuiと同じ形式で出力されている(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 8, 13, 0, 0)
        );

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertOk();

        $response->assertSee('2026年9月8日');
        $response->assertSee('13:00');
    }
}
