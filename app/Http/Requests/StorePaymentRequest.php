<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'amount'  => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'note'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->has('amount')) {
                return;
            }
            $summary = $this->route('student')->paymentSummary();
            if ((int) $this->input('amount') > $summary['remaining']) {
                $v->errors()->add(
                    'amount',
                    'Nominal melebihi sisa kewajiban (Rp'.number_format($summary['remaining'], 0, ',', '.').').'
                );
            }
        });
    }

    public function attributes(): array
    {
        return ['amount' => 'nominal pembayaran', 'paid_at' => 'tanggal transaksi', 'note' => 'keterangan'];
    }
}
