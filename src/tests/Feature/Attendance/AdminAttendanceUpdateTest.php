<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon; 

class AdminAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $user;
    private Attendance $attendance;
    private Rest $rest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

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
    public function 勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $response = $this->actingAs($this->admin, 'admin')->get("/admin/attendance/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細（管理者変更）'); 
        $response->assertSeeText($this->user->name);
        $response->assertSeeText(\Carbon\Carbon::parse($this->attendance->work_date)->format('Y年'));
        $response->assertSeeText(\Carbon\Carbon::parse($this->attendance->work_date)->format('n月j日'));
        $clockInValue = 'value="' . Carbon::parse($this->attendance->clock_in)->format('H:i') . '"';
        $response->assertSee($clockInValue, false);

        $clockOutValue = 'value="' . Carbon::parse($this->attendance->clock_out)->format('H:i') . '"';
        $response->assertSee($clockOutValue, false);

        $restStartValue = 'value="' . Carbon::parse($this->rest->start_time)->format('H:i') . '"';
        $response->assertSee($restStartValue, false);

        $restEndValue = 'value="' . Carbon::parse($this->rest->end_time)->format('H:i') . '"';
        $response->assertSee($restEndValue, false);
    }

    #[Test]
    public function 修正機能_管理者として直接修正が実行されること()
    {
        $response = $this->actingAs($this->admin, 'admin')->post("/admin/attendance/{$this->attendance->id}", [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'remarks' => '管理者修正備考',
            'rest_mod' => [
                $this->rest->id => ['id' => $this->rest->id, 'start' => '13:00', 'end' => '14:00']
            ],
            'rest_add' => [
                ['start' => '17:00', 'end' => '17:30']
            ],
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendance->id,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
        ]);

        $this->assertDatabaseHas('rests', [
            'id' => $this->rest->id,
            'start_time' => '13:00',
            'end_time' => '14:00',
        ]);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $this->attendance->id,
            'start_time' => '17:00',
            'end_time' => '17:30',
        ]);

        $response->assertRedirect("/admin/attendance/{$this->attendance->id}");
    }
}

