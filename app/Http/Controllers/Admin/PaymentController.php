<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeeAdjustmentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\FeeAdjustment;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $period   = PaymentSetting::current();
        $students = User::students()->active()
            ->search($request->query('q'))
            ->withPaymentTotals($period)
            ->orderBy('nis')
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments', compact('period', 'students'));
    }

    /** JSON used to fill the payment-management modal. */
    public function show(User $student): JsonResponse
    {
        $period = PaymentSetting::current();

        return response()->json([
            'student' => ['id' => $student->id, 'nis' => $student->nis, 'name' => $student->name],
            'period'  => ['name' => $period->name, 'due_date' => $period->due_date->format('d/m/Y')],
            'summary' => $student->paymentSummary($period),
            'adjustments' => $student->feeAdjustments()
                ->where('payment_setting_id', $period->id)->latest()->get()
                ->map(fn ($a) => [
                    'amount' => $a->amount,
                    'reason' => $a->reason,
                    'date'   => $a->created_at->format('d/m/Y'),
                ]),
            'transactions' => $student->transactions()
                ->where('payment_setting_id', $period->id)->confirmed()
                ->orderByDesc('paid_at')->orderByDesc('id')->get()
                ->map(fn ($t) => [
                    'amount' => $t->amount,
                    'date'   => $t->paid_at->format('d/m/Y'),
                    'note'   => $t->note,
                ]),
        ]);
    }

    public function storePayment(StorePaymentRequest $request, User $student): RedirectResponse
    {
        // Every payment/installment is a new row; previous rows are never modified.
        PaymentTransaction::create([
            'user_id'            => $student->id,
            'payment_setting_id' => PaymentSetting::current()->id,
            'amount'             => $request->integer('amount'),
            'paid_at'            => $request->date('paid_at'),
            'note'               => $request->input('note'),
            'status'             => PaymentTransaction::STATUS_CONFIRMED,
            'confirmed_by'       => $request->user()->id,
            'confirmed_at'       => now(),
        ]);

        return back()->with('success', "Pembayaran {$student->name} berhasil dicatat.");
    }

    public function storeAdjustment(FeeAdjustmentRequest $request, User $student): RedirectResponse
    {
        $sign = $request->input('adjustment_type') === 'reduce' ? -1 : 1;

        FeeAdjustment::create([
            'user_id'            => $student->id,
            'payment_setting_id' => PaymentSetting::current()->id,
            'amount'             => $sign * $request->integer('adjustment_amount'),
            'reason'             => $request->input('reason'),
            'created_by'         => $request->user()->id,
        ]);

        return back()->with('success', "Penyesuaian biaya {$student->name} berhasil dicatat.");
    }
}
