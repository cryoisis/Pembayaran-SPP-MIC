<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;

/** Students who still have unpaid bills past their due date. */
class ArrearsController extends Controller
{
    public function index(Request $request, BillingService $billing)
    {
        $billing->syncBills();
        $setting = PaymentSetting::current();

        $rows = User::students()->active()
            ->search($request->query('q'))
            ->with('bills.allocations.transaction')
            ->get()
            ->map(function (User $student) use ($setting) {
                $sum = $student->paymentSummary($setting);

                return [
                    'student' => $student,
                    'late' => $sum['late'],
                    'periods' => $sum['late']->pluck('label')->implode(', '),
                    'total' => $sum['total'],
                    'remaining' => $sum['remaining'],
                    'due' => $sum['late']->isNotEmpty() ? $sum['late']->first()->dueDate($setting) : null,
                ];
            })
            ->filter(fn ($r) => $r['late']->isNotEmpty())
            ->sortBy(fn ($r) => $r['due']->timestamp)
            ->values();

        return view('admin.arrears', compact('rows'));
    }
}
