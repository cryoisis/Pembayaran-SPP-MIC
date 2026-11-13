<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\PaymentSetting;
use App\Services\BillingService;

/** Read-only: students/parents only see their own bills, status and schedule. */
class DashboardController extends Controller
{
    public function index(BillingService $billing)
    {
        $user = auth()->user();
        $billing->syncBills($user);

        $setting = PaymentSetting::current();
        $user->load('bills.allocations.transaction');

        $summary = $user->paymentSummary($setting);
        $next = $summary['next'];
        $currentBill = $user->bills->first(fn ($b) => $b->period->isSameMonth(today()));
        $daysLeft = $next ? (int) today()->diffInDays($next->dueDate($setting), false) : null;
        $announcements = Announcement::latest()->get();

        return view('student.dashboard', compact('setting', 'summary', 'currentBill', 'daysLeft', 'announcements'));
    }
}
