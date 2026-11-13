<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\FeeRate;
use App\Models\PaymentSetting;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(BillingService $billing)
    {
        $billing->syncBills();

        $setting = PaymentSetting::current();
        $students = User::students()->active()->with('bills.allocations.transaction')->orderBy('nisn')->get();

        $statusCounts = [
            PaymentSetting::STATUS_PAID => 0,
            PaymentSetting::STATUS_UNPAID => 0,
            PaymentSetting::STATUS_LATE => 0,
        ];
        $totals = ['total' => 0, 'paid' => 0, 'remaining' => 0];
        $arrears = collect();

        foreach ($students as $student) {
            $sum = $student->paymentSummary($setting);
            $statusCounts[$sum['status']]++;
            $totals['total'] += $sum['total'];
            $totals['paid'] += $sum['paid'];
            $totals['remaining'] += $sum['remaining'];

            if ($sum['late']->isNotEmpty()) {
                $arrears->push([
                    'student' => $student,
                    'periods' => $sum['late']->pluck('label')->implode(', '),
                    'remaining' => $sum['remaining'],
                    'due' => $sum['late']->first()->dueDate($setting),
                ]);
            }
        }

        return view('admin.dashboard', [
            'setting' => $setting,
            'feeRate' => FeeRate::current(),
            'studentCount' => $students->count(),
            'statusCounts' => $statusCounts,
            'totals' => $totals,
            'arrears' => $arrears->sortBy(fn ($r) => $r['due']->timestamp)->take(5)->values(),
            'announcements' => Announcement::latest()->get(),
        ]);
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_day' => ['required', 'integer', 'between:1,31'],
            'due_day' => ['required', 'integer', 'between:1,31', 'gte:start_day'],
        ], [
            'due_day.gte' => 'Batas akhir pembayaran tidak boleh lebih awal dari tanggal mulai.',
        ], [
            'start_day' => 'tanggal mulai pembayaran', 'due_day' => 'batas akhir pembayaran',
        ]);

        PaymentSetting::current()->update($data);

        return back()->with('success', 'Jadwal pembayaran berhasil disimpan.');
    }
}
