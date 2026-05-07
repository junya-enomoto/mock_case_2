<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])->name('attendance.break_start');
    Route::post('/attendance/break/end', [AttendanceController::class, 'breakEnd'])->name('attendance.break_end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'submitRequest'])->name('attendance.correction.request');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('auth:admin')->group(function () {
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'showApprovalForm'])->name('stamp_correction_request.approve');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'processApproval'])->name('stamp_correction_request.process_approval');
        Route::get('/attendance/list', [\App\Http\Controllers\AdminAttendanceController::class, 'list'])->name('attendance.list');
        Route::get('/attendance/{id}', [\App\Http\Controllers\AdminAttendanceController::class, 'detail'])->name('attendance.detail');
        Route::post('/attendance/{id}', [\App\Http\Controllers\AdminAttendanceController::class, 'update'])->name('attendance.update');
        Route::get('/staff/list', [\App\Http\Controllers\AdminAttendanceController::class, 'staffList'])->name('staff.list');
        Route::get('/attendance/staff/{id}', [\App\Http\Controllers\AdminAttendanceController::class, 'staffAttendanceList'])->name('staff.attendance.list');
        Route::get('/attendance/staff/{id}/csv', [\App\Http\Controllers\AdminAttendanceController::class, 'exportCsv'])->name('staff.attendance.csv');
    });
});


Route::get('/stamp_correction_request/list', function (Request $request) {
    if (Auth::guard('admin')->check()) {
        $controller = new AdminAttendanceController;

        return $controller->requestList($request);
    } elseif (Auth::guard('web')->check()) {
        $controller = new AttendanceController;

        return $controller->requestList($request);
    }

    return redirect('/login');
})->middleware('auth:web,admin')->name('stamp_correction_request.list');

Route::get('/register', function () {
    return view('auth.register');
})->middleware(['guest'])->name('register');
Route::get('/login', function () {
    return view('auth.login');
})->middleware(['guest'])->name('login');
