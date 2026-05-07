<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Date; 

class AttendanceActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Date::setTestNow(Carbon::create(2023, 6, 1, 0, 0, 0)); 
    }

    protected function tearDown(): void
    {
        Date::setTestNow(null);
        parent::tearDown();
    }


    #[Test]
    public function 現在の日時情報がUIと同じ形式で出力されている()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 10, 0, 0));

        $response = $this->actingAs($this->user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('<p class="attendance__date">2023年6月1日(木)</p>', false); 
        $response->assertSee('<p class="attendance__time" id="realtime">08:00</p>', false); 
    }


    #[Test]
    public function 勤務外の場合、勤怠ステータスが正しく表示される()
    {
        $response = $this->actingAs($this->user)->get('/attendance');
        
        $response->assertSee('<span class="status-label">勤務外</span>', false);
        $response->assertSee('<button type="submit" class="btn-punch">出勤</button>', false); 
    }

    #[Test]
    public function 出勤中の場合、勤怠ステータスが正しく表示される()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        
        $response->assertSee('<span class="status-label">勤務中</span>', false);
        $response->assertSee('<button type="submit" class="btn-punch">退勤</button>', false); 
        $response->assertSee('<button type="submit" class="btn-punch btn-punch-inverted">休憩入</button>', false); 
    }

    #[Test]
    public function 休憩中の場合、勤怠ステータスが正しく表示される()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
        ]);
        Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        
        $response->assertSee('<span class="status-label">休憩中</span>', false);
        $response->assertSee('<button type="submit" class="btn-punch btn-punch-inverted">休憩終</button>', false); 
    }

    #[Test]
    public function 退勤済の場合、勤怠ステータスが正しく表示される()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        
        $response->assertSee('<span class="status-label">退勤済</span>', false);
        $response->assertSee('<p class="text-message">お疲れ様でした。</p>', false); 
        $response->assertDontSee('<button type="submit" class="btn-punch">出勤</button>', false); 
        $response->assertDontSee('<button type="submit" class="btn-punch">退勤</button>', false); 
    }


    #[Test]
    public function 出勤ボタンが正しく機能する()
    {
        $response = $this->actingAs($this->user)->post(route('attendance.clockin'));

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->get('/attendance')->assertSee('<span class="status-label">勤務中</span>', false);
    }

    #[Test]
    public function 出勤は一日一回のみできる()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($this->user)->post(route('attendance.clockin'));

        $count = Attendance::where('user_id', $this->user->id)
                           ->where('work_date', Carbon::today()->format('Y-m-d'))
                           ->count();
        $this->assertEquals(1, $count);
    }

    #[Test]
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 9, 30, 0));
        $this->actingAs($this->user)->post(route('attendance.clockin'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'work_date' => '2023-06-01',
            'clock_in' => '09:30:00', 
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance.list', ['year' => 2023, 'month' => 6]));
        
        $response->assertStatus(200);
        $response->assertSee('<td>06/01(木)</td>', false); 
        $response->assertSee('<td>09:30</td>', false); 
    }


    #[Test]
    public function 休憩ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 9, 0, 0));
        $this->actingAs($this->user)->post(route('attendance.clockin'));

        Carbon::setTestNow(Carbon::create(2023, 6, 1, 12, 0, 0));
        $response = $this->post(route('attendance.break_start'));

        $response->assertRedirect();
        
        $attendance = Attendance::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => null,
        ]);

        $this->get('/attendance')->assertSee('<span class="status-label">休憩中</span>', false);
    }

    #[Test]
    public function 休憩戻ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 9, 0, 0));
        $this->actingAs($this->user)->post(route('attendance.clockin')); 
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 12, 0, 0));
        $this->post(route('attendance.break_start')); 

        Carbon::setTestNow(Carbon::create(2023, 6, 1, 13, 0, 0));
        $response = $this->post(route('attendance.break_end')); 

        $response->assertRedirect();
        
        $attendance = Attendance::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        $this->get('/attendance')->assertSee('<span class="status-label">勤務中</span>', false);
    }

    #[Test]
    public function 休憩は一日に何回でもできる()
    {
        $this->actingAs($this->user)->post(route('attendance.clockin')); 

        $this->post(route('attendance.break_start')); 
        $this->post(route('attendance.break_end')); 
        
        $this->post(route('attendance.break_start')); 

        $attendance = Attendance::where('user_id', $this->user->id)->first();
        $this->assertEquals(2, $attendance->rests()->count());
        $this->get('/attendance')->assertSee('<span class="status-label">休憩中</span>', false);
    }

    #[Test]
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 9, 0, 0));
        $this->actingAs($this->user)->post(route('attendance.clockin')); 
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 12, 0, 0));
        $this->post(route('attendance.break_start')); 
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 13, 15, 0));
        $this->post(route('attendance.break_end')); 
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 18, 0, 0));
        $this->post(route('attendance.clockout')); 

        $response = $this->actingAs($this->user)->get(route('attendance.list', ['year' => 2023, 'month' => 6]));
        
        $response->assertStatus(200);
        $response->assertSee('<td>1:15</td>', false); 
    }

    // --- 8. 退勤機能 ---

    #[Test]
    public function 退勤ボタンが正しく機能する()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 9, 0, 0));
        $this->actingAs($this->user)->post(route('attendance.clockin')); 

        Carbon::setTestNow(Carbon::create(2023, 6, 1, 18, 30, 0));
        $response = $this->post(route('attendance.clockout')); 

        $response->assertRedirect();
        
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'clock_out' => '18:30:00',
        ]);

        $this->get('/attendance')->assertSee('<span class="status-label">退勤済</span>', false);
    }

    #[Test]
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 9, 0, 0));
        $this->actingAs($this->user)->post(route('attendance.clockin')); 
        Carbon::setTestNow(Carbon::create(2023, 6, 1, 18, 45, 0));
        $this->post(route('attendance.clockout')); 

        $response = $this->get(route('attendance.list', ['year' => 2023, 'month' => 6]));
        
        $response->assertStatus(200);
        $response->assertSee('<td>18:45</td>', false); 
    }
}
