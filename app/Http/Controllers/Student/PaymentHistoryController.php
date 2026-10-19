<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;

class PaymentHistoryController extends Controller
{
    public function index()
    {
        $user    = auth()->user();   // students only ever see their own data
        $period  = PaymentSetting::current();
        $summary = $user->paymentSummary($period);

        $transactions = $user->transactions()
            ->where('payment_setting_id', $period->id)
            ->confirmed()
            ->orderBy('paid_at')->orderBy('id')
            ->get();

        // Running total after each installment, then newest first.
        $running = 0;
        foreach ($transactions as $trx) {
            $running += $trx->amount;
            $trx->cumulative      = $running;
            $trx->remaining_after = max(0, $summary['total'] - $running);
        }
        $transactions = $transactions->reverse()->values();

        return view('student.history', compact('period', 'summary', 'transactions'));
    }
}
