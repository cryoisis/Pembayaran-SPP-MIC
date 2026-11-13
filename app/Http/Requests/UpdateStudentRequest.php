<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'nisn' => ['required', 'string', 'max:30', Rule::unique('users', 'nisn')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],   // blank = keep old password
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.unique' => 'NISN sudah dipakai siswa lain.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ];
    }

    public function attributes(): array
    {
        return ['nisn' => 'NISN', 'name' => 'nama lengkap', 'password' => 'kata sandi'];
    }
}
