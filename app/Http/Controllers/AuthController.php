<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'nisn' => $request->input('nisn'),
            'password' => $request->input('password'),
            'is_active' => true,               // deactivated students cannot log in
        ];

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['nisn' => 'NISN/akun atau kata sandi salah, atau akun tidak aktif.'])
                ->onlyInput('nisn');
        }

        $request->session()->regenerate();

        // Role is derived from the account, not from the form.
        return redirect()->route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
