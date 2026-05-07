<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Attendance $attendance;

    private Rest $rest; 

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => '2023-06-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->rest = Rest::create([
            'attendance_id' => $this->attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);
    }

    #[Test]
    public function 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)->post("/attendance/detail/{$this->attendance->id}", [
            'clock_in' => '19:00', 
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
        ]);

        $response->assertSessionHasErrors(['clock_out' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    #[Test]
    public function 休憩開始時間が出勤時間より前になっている場合、エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)->post("/attendance/detail/{$this->attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
            'rest_mod' => [
                $this->rest->id => [
                    'id' => $this->rest->id,
                    'start' => '08:00', 
                    'end' => '13:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(["rest_mod.{$this->rest->id}.start" => '休憩時間が不適切な値です']);
    }

    #[Test]
    public function 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)->post("/attendance/detail/{$this->attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
            'rest_mod' => [
                $this->rest->id => [
                    'id' => $this->rest->id,
                    'start' => '19:00',
                    'end' => '20:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(["rest_mod.{$this->rest->id}.start" => '休憩時間が不適切な値です']);
    }

    #[Test]
    public function 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)->post("/attendance/detail/{$this->attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テスト備考',
            'rest_mod' => [
                $this->rest->id => [
                    'id' => $this->rest->id,
                    'start' => '12:00',
                    'end' => '19:00',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(["rest_mod.{$this->rest->id}.end" => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    #[Test]
    public function 備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $response = $this->actingAs($this->user)->post("/attendance/detail/{$this->attendance->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => '',
        ]);

        $response->assertSessionHasErrors(['remarks' => '備考を記入してください']);
    }
}
