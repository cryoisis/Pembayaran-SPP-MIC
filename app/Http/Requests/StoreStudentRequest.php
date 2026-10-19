<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nis'      => ['required', 'string', 'max:30', 'unique:users,nis'],
            'name'     => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique'   => 'NIS sudah terdaftar.',
            'password.min' => 'Password minimal 6 karakter.',
        ];
    }

    public function attributes(): array
    {
        return ['nis' => 'NIS', 'name' => 'nama lengkap', 'password' => 'password'];
    }
}
