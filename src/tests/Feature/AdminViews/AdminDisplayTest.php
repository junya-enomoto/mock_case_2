<?php

namespace Tests\Feature\AdminViews;

use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Date;

class AdminDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $userA;
    private User $userB;
    private Attendance $attendanceA;
    private Attendance $attendanceB;
    private AttendanceCorrectionRequest $pendingRequest;

    protected function setUp(): void
    {
        parent::setUp();
        
        Date::setTestNow(Carbon::create(2023, 6, 1, 10, 0, 0));

        $this->admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $this->userA = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@test.com']);
        $this->attendanceA = Attendance::create([
            'user_id' => $this->userA->id,
            'work_date' => '2023-06-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        Rest::create(['attendance_id' => $this->attendanceA->id, 'start_time' => '12:00:00', 'end_time' => '13:00:00']);

        Attendance::create([
            'user_id' => $this->userA->id,
            'work_date' => '2023-05-15',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        $this->userB = User::factory()->create(['name' => '鈴木花子', 'email' => 'suzuki@test.com']);
        $this->attendanceB = Attendance::create([
            'user_id' => $this->userB->id,
            'work_date' => '2023-06-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $this->pendingRequest = AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendanceA->id,
            'clock_in_new' => '09:15:00',
            'clock_out_new' => '18:15:00',
            'remarks' => '遅刻修正',
            'status' => 'pending',
        ]);

        AttendanceCorrectionRequest::create([
            'attendance_id' => $this->attendanceB->id,
            'clock_in_new' => '09:30:00',
            'clock_out_new' => '18:30:00',
            'remarks' => '承認済み',
            'status' => 'approved',
        ]);
    }

    protected function tearDown(): void
    {
        Date::setTestNow(null);
        parent::tearDown();
    }

    #[Test]
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.attendance.list'));
        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('09:00'); 
        $response->assertSeeText('鈴木花子');
        $response->assertSeeText('10:00');
    }

    #[Test]
    public function 遷移した際に現在の日付が表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.attendance.list'));
        $response->assertStatus(200);
        $response->assertSeeText('2023年06月01日'); 
    }

    #[Test]
    public function 前日を押下した時に前の日の勤怠情報が表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.attendance.list', ['date' => '2023-05-31']));
        $response->assertStatus(200);
        $response->assertSeeText('2023年05月31日');
        $response->assertDontSeeText('山田太郎'); 
    }

    #[Test]
    public function 翌日を押下した時に次の日の勤怠情報が表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.attendance.list', ['date' => '2023-06-02']));
        $response->assertStatus(200);
        $response->assertSeeText('2023年06月02日');
        $response->assertDontSeeText('山田太郎');
    }

    #[Test]
    public function 管理者ユーザーが全一般ユーザーの氏名メールアドレスを確認できる()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.staff.list'));
        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('yamada@test.com');
        $response->assertSeeText('鈴木花子');
        $response->assertSeeText('suzuki@test.com');
    }

    #[Test]
    public function ユーザーの勤怠情報が正しく表示される()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.staff.attendance.list', ['id' => $this->userA->id]));
        $response->assertStatus(200);
        $response->assertSeeText('山田太郎さんの勤怠一覧');
        $response->assertSeeText('06/01'); 
        $response->assertDontSeeText('鈴木花子'); 
    }

    #[Test]
    public function 前月を押下した時に表示月の前月の情報が表示される_管理者()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.staff.attendance.list', ['id' => $this->userA->id, 'year' => 2023, 'month' => 5]));
        $response->assertStatus(200);
        $response->assertSeeText('2023年05月'); 
        $response->assertSeeText('05/15'); // 
        $response->assertDontSeeText('06/01'); 
    }

    #[Test]
    public function 翌月を押下した時に表示月の翌月の情報が表示される_管理者()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.staff.attendance.list', ['id' => $this->userA->id, 'year' => 2023, 'month' => 7]));
        $response->assertStatus(200);
        $response->assertSeeText('2023年07月'); 
        $response->assertDontSeeText('06/01'); 
    }

    #[Test]
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する_管理者()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.attendance.detail', ['id' => $this->attendanceA->id]));
        $response->assertStatus(200);
        $response->assertSeeText('勤怠詳細（管理者変更）'); 
        $response->assertSeeText('山田太郎'); 
    }


    #[Test]
    public function 承認待ちの修正申請が全て表示されている()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('stamp_correction_request.list', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('遅刻修正');
        $response->assertSeeText('承認待ち');
        $response->assertDontSeeText('鈴木花子'); 
    }

    #[Test]
    public function 承認済みの修正申請が全て表示されている()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('stamp_correction_request.list', ['status' => 'approved']));
        $response->assertStatus(200);
        $response->assertSeeText('鈴木花子');
        $response->assertSeeText('承認済み');
        $response->assertDontSeeText('遅刻修正'); 
    }

    #[Test]
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.stamp_correction_request.approve', ['attendance_correct_request_id' => $this->pendingRequest->id]));
        $response->assertStatus(200);
        $response->assertSeeText('山田太郎');
        $response->assertSeeText('09:15'); 
        $response->assertSeeText('18:15'); 
        $response->assertSeeText('遅刻修正'); 
        $response->assertSee('承認', false); 
    }

    #[Test]
    public function 修正申請の承認処理が正しく行われる()
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson(route('admin.stamp_correction_request.process_approval', ['attendance_correct_request_id' => $this->pendingRequest->id]), [
            'action' => 'approve'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']); 

        $this->assertDatabaseHas('attendances', [
            'id' => $this->attendanceA->id,
            'clock_in' => '09:15:00',
            'clock_out' => '18:15:00',
        ]);

        $this->assertDatabaseHas('correction', [
            'id' => $this->pendingRequest->id,
            'status' => 'approved',
        ]);
    }
}
