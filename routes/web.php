<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Student;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// {student} always resolves to a user with the student role.
Route::bind('student', fn ($id) => User::students()->findOrFail($id));

Route::redirect('/', '/login');

// ---- Unified login / logout ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Student area ----
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/history', [Student\PaymentHistoryController::class, 'index'])->name('history');
});

// ---- Admin area ----
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::put('/schedule', [Admin\DashboardController::class, 'updateSchedule'])->name('schedule.update');
    Route::put('/announcement', [Admin\DashboardController::class, 'updateAnnouncement'])->name('announcement.update');

    Route::get('/students', [Admin\StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [Admin\StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{student}', [Admin\StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [Admin\StudentController::class, 'destroy'])->name('students.destroy');

    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{student}', [Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{student}/transactions', [Admin\PaymentController::class, 'storePayment'])->name('payments.transactions.store');
    Route::post('/payments/{student}/adjustments', [Admin\PaymentController::class, 'storeAdjustment'])->name('payments.adjustments.store');
});
