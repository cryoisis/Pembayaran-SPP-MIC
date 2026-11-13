<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeeRateRequest;
use App\Models\Bill;
use App\Models\FeeRate;
use App\Models\PaymentAllocation;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;

/** SPP rates per academic year. Old rates stay in the list so history is traceable. */
class FeeRateController extends Controller
{
    public function index()
    {
        $rates = FeeRate::orderByDesc('effective_from')->get();

        // Rates already used by transactions are locked (create a new rate instead).
        $lockedIds = Bill::whereIn('id', PaymentAllocation::select('bill_id'))
            ->distinct()->pluck('fee_rate_id')->all();

        return view('admin.fee-rates', [
            'rates' => $rates,
            'current' => FeeRate::current(),
            'lockedIds' => $lockedIds,
        ]);
    }

    public function store(FeeRateRequest $request, BillingService $billing): RedirectResponse
    {
        FeeRate::create($request->validated());
        $billing->syncBills();      // bills of earlier years are untouched

        return back()->with('success', 'Tarif SPP berhasil ditambahkan.');
    }

    public function update(FeeRateRequest $request, FeeRate $feeRate, BillingService $billing): RedirectResponse
    {
        // Validation guarantees the rate has no transactions, so its bills can be rebuilt safely.
        $feeRate->update($request->validated());
        $feeRate->bills()->delete();
        $billing->syncBills();

        return back()->with('success', 'Tarif SPP berhasil diperbarui.');
    }
}
