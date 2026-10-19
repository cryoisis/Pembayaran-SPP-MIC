<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FeeAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'adjustment_type'   => ['required', 'in:add,reduce'],
            'adjustment_amount' => ['required', 'integer', 'min:1'],
            'reason'            => ['required', 'string', 'max:255'],   // reason is mandatory
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $summary = $this->route('student')->paymentSummary();
            $delta   = (int) $this->input('adjustment_amount') * ($this->input('adjustment_type') === 'reduce' ? -1 : 1);

            if ($summary['total'] + $delta < $summary['paid']) {
                $v->errors()->add(
                    'adjustment_amount',
                    'Pengurangan terlalu besar: total kewajiban tidak boleh lebih kecil dari pembayaran yang sudah dicatat.'
                );
            }
        });
    }

    public function messages(): array
    {
        return ['reason.required' => 'Keterangan alasan penyesuaian wajib diisi.'];
    }

    public function attributes(): array
    {
        return [
            'adjustment_type'   => 'jenis penyesuaian',
            'adjustment_amount' => 'nominal penyesuaian',
            'reason'            => 'keterangan',
        ];
    }
}
