<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = User::students()
            ->search($request->query('q'))
            ->orderBy('nis')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students', compact('students'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        User::create($request->validated() + ['role' => User::ROLE_STUDENT, 'is_active' => true]);

        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        $data = $request->safe()->only(['nis', 'name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->filled('password')) {       // blank password = keep the old one
            $data['password'] = $request->input('password');
        }

        $student->update($data);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(User $student): RedirectResponse
    {
        // Students with payment history are deactivated so records stay traceable.
        if ($student->transactions()->exists() || $student->feeAdjustments()->exists()) {
            $student->update(['is_active' => false]);

            return back()->with('success', 'Siswa memiliki riwayat pembayaran, sehingga dinonaktifkan (data dipertahankan).');
        }

        $student->delete();

        return back()->with('success', 'Siswa berhasil dihapus.');
    }
}
