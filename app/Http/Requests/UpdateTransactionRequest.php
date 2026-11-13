<?php

namespace App\Http\Requests;

use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Correction of an existing transaction (date, method, note, covered months). */
class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'bill_ids' => ['required', 'array', 'min:1'],
            'bill_ids.*' => ['integer'],
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::in(array_keys(PaymentTransaction::METHODS))],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $transaction = $this->route('transaction');

            app(PaymentService::class)->validateSelection(
                $v, $transaction->student, $this->input('bill_ids'), (int) $this->input('amount'), $transaction
            );
        });
    }

    public function messages(): array
    {
        return ['bill_ids.required' => 'Pilih minimal satu periode pembayaran.'];
    }

    public function attributes(): array
    {
        return [
            'bill_ids' => 'periode pembayaran', 'amount' => 'nominal pembayaran',
            'paid_at' => 'tanggal transaksi', 'method' => 'metode pembayaran', 'note' => 'keterangan',
        ];
    }
}
