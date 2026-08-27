<?php

use App\Http\Controllers\Api\ApplicationDataController;
use App\Http\Controllers\Api\AttendanceSyncController;
use App\Http\Controllers\Api\ValidateTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'tenant.context',
    'tenant.active',
])->group(function (): void {
Route::get('/validatetoken', [ValidateTokenController::class, 'index'])
    ->name('api.validate.token');

Route::get('/autosync', [AttendanceSyncController::class, 'index'])
    ->name('api.autosync.students');

Route::post('/attendance/sync', [AttendanceSyncController::class, 'store'])
    ->name('api.attendance.sync');

Route::get('/rfid/cards', [AttendanceSyncController::class, 'rfidCards'])
    ->name('api.rfid.cards');

Route::post('/attendance/rfid', [AttendanceSyncController::class, 'scanRfid'])
    ->name('api.attendance.rfid');

Route::get('/application/school', [ApplicationDataController::class, 'getSchoolData'])
    ->name('api.application.school');

Route::get('/application/student-images', [ApplicationDataController::class, 'downloadStudentImages'])
    ->name('api.application.student_images');

Route::get('/application/desktop-updates/{file}', [ApplicationDataController::class, 'downloadDesktopUpdate'])
    ->where('file', '.*')
    ->name('api.application.desktop_updates');
});
