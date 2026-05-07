<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow(Carbon::create(2023, 6, 1, 10, 0, 0));
        Notification::fake(); 
    }

    protected function tearDown(): void
    {
        Date::setTestNow(null);
        parent::tearDown();
    }

    #[Test]
    public function 会員登録後、認証メールが送信される()
    {
        $testEmail = 'test_new_user@example.com';

        $response = $this->post('/register', [
            'name' => 'Test New User',
            'email' => $testEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/attendance'); 
        
        $registeredUser = User::where('email', $testEmail)->first();
        $this->assertNotNull($registeredUser); 
        
        Notification::assertSentTo($registeredUser, VerifyEmail::class, function ($notification, $channels) use ($registeredUser) {
            return $notification instanceof VerifyEmail; 
        });
    }

    #[Test]
    public function メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('href="http://localhost:8025"', false);
        $response->assertSeeText('認証はこちらから');
    }

    #[Test]
    public function メール認証サイトのメール認証を完了すると勤怠登録画面に遷移する()
    {
        Event::fake(); 
        $user = User::factory()->unverified()->create();
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                $user->id,
                sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });

        $this->assertNotNull($user->fresh()->email_verified_at);

        $response->assertRedirect('/attendance?verified=1');
    }
}

