<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\FeeRate;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** On-screen payment report (no PDF/print export). Uses confirmed, still-valid payments only. */
class ReportController extends Controller
{
    public function index(Request $request, BillingService $billing)
    {
        $billing->syncBills();
        $setting = PaymentSetting::current();
        $rate = FeeRate::current();

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'required_with:from', 'after_or_equal:from'],
            'student_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                PaymentSetting::STATUS_PAID, PaymentSetting::STATUS_UNPAID, PaymentSetting::STATUS_LATE,
            ])],
        ]);

        // Report window: a month, a date range, or (default) the current academic year.
        if (! empty($filters['month'])) {
            $from = Carbon::createFromFormat('!Y-m', $filters['month'])->startOfMonth();
            $to = $from->copy()->endOfMonth();
        } elseif (! empty($filters['from'])) {
            $from = Carbon::parse($filters['from'])->startOfDay();
            $to = Carbon::parse($filters['to'])->endOfDay();
        } elseif ($rate) {
            $from = $rate->effective_from->copy()->startOfDay();
            $to = $rate->effective_to->copy()->endOfDay();
        } else {
            $from = today()->startOfYear();
            $to = today()->endOfYear();
        }

        $studentFilter = function ($s) use ($filters) {
            $s->when(! empty($filters['student_id']), fn ($q) => $q->where('id', $filters['student_id']))
                ->search($filters['q'] ?? null);
        };

        // Bills of the months touched by the window, grouped per student.
        $bills = Bill::with(['student', 'allocations.transaction'])
            ->whereBetween('period', [$from->copy()->startOfMonth()->toDateString(), $to->copy()->startOfMonth()->toDateString()])
            ->whereHas('student', $studentFilter)
            ->get();

        $rows = $bills->groupBy('user_id')->map(function ($group) use ($setting) {
            $total = (int) $group->sum('amount');
            $paid = (int) $group->filter(fn (Bill $b) => $b->isPaid())->sum('amount');
            $unpaid = $group->reject(fn (Bill $b) => $b->isPaid());

            return [
                'student' => $group->first()->student,
                'periods' => $group->sortBy(fn (Bill $b) => $b->period->timestamp)->pluck('label')->implode(', '),
                'total' => $total,
                'paid' => $paid,
                'remaining' => max(0, $total - $paid),
                'status' => $unpaid->contains(fn (Bill $b) => $b->isLate($setting))
                    ? PaymentSetting::STATUS_LATE
                    : ($unpaid->isNotEmpty() ? PaymentSetting::STATUS_UNPAID : PaymentSetting::STATUS_PAID),
            ];
        })
            ->when(! empty($filters['status']), fn ($c) => $c->where('status', $filters['status']))
            ->sortBy(fn ($r) => $r['student']->nisn)
            ->values();

        $transactions = PaymentTransaction::confirmed()
            ->with(['student', 'bills'])
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->whereHas('student', $studentFilter)
            ->when(! empty($filters['status']), fn ($q) => $q->whereIn('user_id', $rows->pluck('student.id')))
            ->orderBy('paid_at')->orderBy('id')
            ->get();

        $summary = [
            'total' => (int) $rows->sum('total'),
            'paid' => (int) $transactions->sum('amount'),
            'remaining' => max(0, (int) $rows->sum('total') - (int) $transactions->sum('amount')),
        ];

        return view('admin.reports', [
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'summary' => $summary,
            'transactions' => $transactions,
            'students' => User::students()->orderBy('nisn')->get(['id', 'nisn', 'name']),
        ]);
    }
}
