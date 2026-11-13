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

// ---- Student / parent area (read-only dashboard) ----
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
});

// ---- Admin / bendahara area ----
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::put('/schedule', [Admin\DashboardController::class, 'updateSchedule'])->name('schedule.update');

    Route::post('/announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('/students', [Admin\StudentController::class, 'index'])->name('students.index');
    Route::post('/students', [Admin\StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{student}', [Admin\StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [Admin\StudentController::class, 'destroy'])->name('students.destroy'); // deactivates

    Route::get('/fee-rates', [Admin\FeeRateController::class, 'index'])->name('fee-rates.index');
    Route::post('/fee-rates', [Admin\FeeRateController::class, 'store'])->name('fee-rates.store');
    Route::put('/fee-rates/{feeRate}', [Admin\FeeRateController::class, 'update'])->name('fee-rates.update');

    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{student}', [Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{student}/transactions', [Admin\PaymentController::class, 'storePayment'])->name('payments.transactions.store');

    Route::get('/transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [Admin\TransactionController::class, 'show'])->name('transactions.show');
    Route::put('/transactions/{transaction}', [Admin\TransactionController::class, 'update'])->name('transactions.update');
    Route::post('/transactions/{transaction}/confirm', [Admin\TransactionController::class, 'confirm'])->name('transactions.confirm');
    Route::delete('/transactions/{transaction}', [Admin\TransactionController::class, 'cancel'])->name('transactions.cancel');

    Route::get('/arrears', [Admin\ArrearsController::class, 'index'])->name('arrears.index');
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
});
