<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $period   = PaymentSetting::current();
        $students = User::students()->active()->withPaymentTotals($period)->get();

        $statusCounts = collect([
            PaymentSetting::STATUS_PAID, PaymentSetting::STATUS_INSTALLMENT,
            PaymentSetting::STATUS_UNPAID, PaymentSetting::STATUS_LATE,
            PaymentSetting::STATUS_LATE_INSTALLMENT,
        ])->mapWithKeys(fn ($s) => [$s => 0])->all();

        $totals = ['obligation' => 0, 'paid' => 0, 'remaining' => 0];

        foreach ($students as $student) {
            $sum = $student->paymentSummary($period);
            $statusCounts[$sum['status']]++;
            $totals['obligation'] += $sum['total'];
            $totals['paid']       += $sum['paid'];
            $totals['remaining']  += $sum['remaining'];
        }

        return view('admin.dashboard', [
            'period'       => $period,
            'studentCount' => $students->count(),
            'statusCounts' => $statusCounts,
            'totals'       => $totals,
        ]);
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'start_date'   => ['required', 'date'],
            'due_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'standard_fee' => ['required', 'integer', 'min:0'],
        ], [], [
            'name' => 'nama periode', 'start_date' => 'tanggal mulai',
            'due_date' => 'batas akhir', 'standard_fee' => 'nominal standar',
        ]);

        PaymentSetting::current()->update($data);

        return back()->with('success', 'Jadwal pembayaran berhasil disimpan.');
    }

    public function updateAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'announcement_title' => ['nullable', 'string', 'max:150'],
            'announcement_body'  => ['nullable', 'string', 'max:5000'],
        ], [], [
            'announcement_title' => 'judul pengumuman', 'announcement_body' => 'isi pengumuman',
        ]);

        PaymentSetting::current()->update($data);

        return back()->with('success', 'Pengumuman berhasil disimpan dan tampil di dashboard siswa.');
    }
}
