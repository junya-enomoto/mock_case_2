<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {

            for ($i = 1; $i < 30; $i++) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');

                if (rand(0, 9) < 2) {
                    continue;
                }

                $clockIn = Carbon::parse($date.' 09:00:00');
                $clockOut = Carbon::parse($date.' 18:00:00');

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date,
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut->format('H:i:s'),
                ]);

                $restStart = Carbon::parse($date.' 12:00:00');
                $restEnd = Carbon::parse($date.' 13:00:00');

                if ($restStart->between($clockIn, $clockOut) && $restEnd->between($clockIn, $clockOut)) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $restStart->format('H:i:s'),
                        'end_time' => $restEnd->format('H:i:s'),
                    ]);
                }

                if (rand(0, 9) < 1) {
                    AttendanceCorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'clock_in_new' => $clockIn->copy()->addHour()->format('H:i:s'),
                        'clock_out_new' => $clockOut->format('H:i:s'),
                        'remarks' => '電車の遅延のため',
                        'status' => 'pending',
                    ]);
                }
            }
        }
    }
}
