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
            'nisn' => ['required', 'string', 'max:30', 'unique:users,nisn'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.unique' => 'NISN sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ];
    }

    public function attributes(): array
    {
        return ['nisn' => 'NISN', 'name' => 'nama lengkap', 'password' => 'kata sandi'];
    }
}
