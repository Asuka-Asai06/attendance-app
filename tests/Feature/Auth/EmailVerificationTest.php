<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録後に認証メールが送信される(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();

        $user = User::where('email', 'test@example.com')
            ->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_認証はこちらからを押すとメール認証サイトへ遷移する(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('verification.notice'));

        $response->assertOk();

        $response->assertSee(
            'href="http://localhost:8025"',
            false
        );

        $response->assertSee('認証はこちらから');
    }

    public function test_メール認証を完了すると勤怠登録画面に遷移する(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)
            ->get($verificationUrl);

        $response->assertRedirectContains(route('attendance.index'));

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_認証メール再送ボタンを押すと認証メールが再送される(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_未認証ユーザーは勤怠画面へアクセスできない(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertRedirect(route('verification.notice'));
    }
}
