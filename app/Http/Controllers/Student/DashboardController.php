<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;

class DashboardController extends Controller
{
    public function index()
    {
        $period   = PaymentSetting::current();
        $summary  = auth()->user()->paymentSummary($period);
        $daysLeft = (int) today()->diffInDays($period->due_date, false);

        return view('student.dashboard', compact('period', 'summary', 'daysLeft'));
    }
}
