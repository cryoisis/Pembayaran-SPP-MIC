<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Bill;
use App\Models\FeeRate;
use App\Models\PaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billing, private PaymentService $payments) {}

    public function index(Request $request)
    {
        $this->billing->syncBills();

        $setting = PaymentSetting::current();
        $rate = FeeRate::current();
        $students = User::students()->active()
            ->search($request->query('q'))
            ->with('bills.allocations.transaction')
            ->orderBy('nisn')
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments', compact('setting', 'rate', 'students'));
    }

    /** JSON used to fill the payment-management modal / drawer. Direct browser visits redirect to index. */
    public function show(Request $request, User $student): JsonResponse|RedirectResponse
    {
        $this->billing->syncBills($student);

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->route('admin.payments.index', ['student' => $student->id]);
        }

        $setting = PaymentSetting::current();
        $student->load('bills.allocations.transaction');

        $summary = $student->paymentSummary($setting);
        $bookable = $this->payments->filterBookable($student->bills)->pluck('id')->all();
        $rate = FeeRate::current();

        return response()->json([
            'student' => ['id' => $student->id, 'nisn' => $student->nisn, 'name' => $student->name],
            'rate' => $rate ? ['academic_year' => $rate->academic_year, 'amount' => $rate->amount] : null,
            'summary' => Arr::only($summary, ['total', 'paid', 'remaining', 'status']),
            'bills' => $student->bills->sortBy(fn (Bill $b) => $b->period->timestamp)->values()
                ->map(fn (Bill $b) => [
                    'id' => $b->id,
                    'label' => $b->label,
                    'amount' => $b->amount,
                    'due_date' => $b->dueDate($setting)->format('d/m/Y'),
                    'status' => $b->statusFor($setting),
                    'pending' => $b->hasPendingTransaction(),
                    'selectable' => in_array($b->id, $bookable),
                ]),
            'transactions' => $student->transactions()->with('bills')
                ->orderByDesc('paid_at')->orderByDesc('id')->get()
                ->map(fn (PaymentTransaction $t) => [
                    'date' => $t->paid_at->format('d/m/Y'),
                    'amount' => $t->amount,
                    'method' => $t->method_label,
                    'periods' => $t->period_labels,
                    'status' => $t->status,
                    'status_label' => $t->status_label,
                    'status_color' => $t->status_color,
                    'url' => route('admin.transactions.show', $t),
                ]),
        ]);
    }

    public function storePayment(StorePaymentRequest $request, User $student): RedirectResponse
    {
        $confirm = $request->input('action') === 'confirm';

        $this->payments->create($student, $request->validated(), $request->user(), $confirm);

        return back()->with('success', $confirm
            ? "Pembayaran {$student->name} berhasil dicatat dan dikonfirmasi."
            : "Pembayaran {$student->name} disimpan, menunggu konfirmasi (menu Transaksi).");
    }
}
