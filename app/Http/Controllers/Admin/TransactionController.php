<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request)
    {
        $transactions = PaymentTransaction::with(['student', 'bills'])
            ->when($request->query('q'), fn ($q, $term) => $q->whereHas('student', fn ($s) => $s->search($term)))
            ->when($request->query('period'), fn ($q, $month) => $q->whereHas('bills', fn ($b) => $b->where('period', $month.'-01')))
            ->when($request->query('from'), fn ($q, $date) => $q->whereDate('paid_at', '>=', $date))
            ->when($request->query('to'), fn ($q, $date) => $q->whereDate('paid_at', '<=', $date))
            ->when($request->query('method'), fn ($q, $method) => $q->where('method', $method))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('paid_at')->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }

    public function show(PaymentTransaction $transaction)
    {
        $transaction->load(['student', 'bills', 'logs.user', 'creator', 'confirmer', 'canceller']);

        // Months that can be (re)assigned to this transaction when correcting it.
        $available = $this->payments->bookableBills($transaction->student, $transaction);
        $selectedIds = $transaction->bills->pluck('id')->all();

        return view('admin.transactions.show', compact('transaction', 'available', 'selectedIds'));
    }

    public function update(UpdateTransactionRequest $request, PaymentTransaction $transaction): RedirectResponse
    {
        abort_if($transaction->isCancelled(), 403, 'Transaksi yang sudah dibatalkan tidak dapat diubah.');

        $this->payments->update($transaction, $request->validated(), $request->user());

        return back()->with('success', 'Transaksi berhasil diperbarui. Status pembayaran dihitung ulang.');
    }

    public function confirm(Request $request, PaymentTransaction $transaction): RedirectResponse
    {
        abort_unless($transaction->isPending(), 403, 'Hanya transaksi yang belum dikonfirmasi yang dapat dikonfirmasi.');

        $this->payments->confirm($transaction, $request->user());

        return back()->with('success', 'Transaksi dikonfirmasi. Status pembayaran siswa diperbarui.');
    }

    /** "Hapus" = batalkan: the record stays (with reason) but is no longer counted. */
    public function cancel(Request $request, PaymentTransaction $transaction): RedirectResponse
    {
        abort_if($transaction->isCancelled(), 403, 'Transaksi sudah dibatalkan.');

        $data = $request->validate(
            ['cancel_reason' => ['required', 'string', 'max:255']],
            ['cancel_reason.required' => 'Alasan pembatalan wajib diisi.'],
        );

        $this->payments->cancel($transaction, $request->user(), $data['cancel_reason']);

        return redirect()->route('admin.transactions.show', $transaction)
            ->with('success', 'Transaksi dibatalkan. Status pembayaran dihitung ulang.');
    }
}
