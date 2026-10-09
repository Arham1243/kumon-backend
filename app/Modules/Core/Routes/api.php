<?php

use App\Modules\Core\Http\Controllers\AttendanceController;
use App\Modules\Core\Http\Controllers\AttendanceExportController;
use App\Modules\Core\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

Route::middleware(['auth.any:sanctum_admin', 'check.active'])->group(function () {
    Route::middleware(['role.permission:core.students.view'])->group(function () {
        Orion::resource('students', StudentController::class, [
            'only' => ['index', 'search', 'show'],
        ])->withoutBatch();
    });

    Route::middleware(['role.permission:core.students.create'])->group(function () {
        Orion::resource('students', StudentController::class, [
            'only' => ['store'],
        ])->withoutBatch();
    });

    Route::middleware(['role.permission:core.students.edit'])->group(function () {
        Orion::resource('students', StudentController::class, [
            'only' => ['update'],
        ])->withoutBatch();
    });

    Route::middleware(['role.permission:core.students.delete'])->group(function () {
        Route::delete('students/{student}/delete', [StudentController::class, 'deleteStudent']);
    });

    Route::middleware(['role.permission:core.attendance.view'])->group(function () {
        Route::post('attendances/export', [AttendanceExportController::class, 'export']);
        Route::get('attendances/student-options', [AttendanceController::class, 'studentOptions']);
        Orion::resource('attendances', AttendanceController::class, [
            'only' => ['index', 'search', 'show'],
        ])->withoutBatch();
    });

    Route::middleware(['role.permission:core.attendance.create'])->group(function () {
        Orion::resource('attendances', AttendanceController::class, [
            'only' => ['store'],
        ])->withoutBatch();
    });

    Route::middleware(['role.permission:core.attendance.edit'])->group(function () {
        Orion::resource('attendances', AttendanceController::class, [
            'only' => ['update'],
        ])->withoutBatch();
    });

    Route::middleware(['role.permission:core.attendance.delete'])->group(function () {
        Route::delete('attendances/{attendance}/delete', [AttendanceController::class, 'deleteAttendance']);
    });
});
