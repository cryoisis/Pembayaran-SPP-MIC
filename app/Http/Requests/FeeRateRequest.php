<?php

namespace App\Http\Requests;

use App\Models\FeeRate;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/** Add / change the SPP rate of an academic year. */
class FeeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $rate = $this->route('feeRate');

        return [
            'academic_year' => ['required', 'string', 'max:20', Rule::unique('fee_rates', 'academic_year')->ignore($rate?->id)],
            'amount' => ['required', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after:effective_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $rate = $this->route('feeRate');
            $from = Carbon::parse($this->input('effective_from'))->toDateString();
            $to = Carbon::parse($this->input('effective_to'))->toDateString();

            $overlap = FeeRate::query()
                ->when($rate, fn ($q) => $q->where('id', '!=', $rate->id))
                ->whereDate('effective_from', '<=', $to)
                ->whereDate('effective_to', '>=', $from)
                ->exists();

            if ($overlap) {
                $v->errors()->add('effective_from', 'Periode berlaku bertabrakan dengan tarif tahun ajaran lain.');
            }

            // A rate already used by transactions is frozen; a new academic year needs a new rate.
            if ($rate && $rate->hasTransactions() && (
                (int) $this->input('amount') !== $rate->amount
                || $from !== $rate->effective_from->toDateString()
                || $to !== $rate->effective_to->toDateString()
                || $this->input('academic_year') !== $rate->academic_year
            )) {
                $v->errors()->add('amount', 'Tarif ini sudah dipakai transaksi sehingga tidak dapat diubah. Tambahkan tarif baru untuk tahun ajaran berikutnya.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'academic_year' => 'tahun ajaran', 'amount' => 'nominal SPP',
            'effective_from' => 'tanggal mulai berlaku', 'effective_to' => 'tanggal akhir berlaku',
        ];
    }
}
