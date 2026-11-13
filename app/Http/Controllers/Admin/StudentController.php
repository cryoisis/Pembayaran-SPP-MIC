<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = User::students()
            ->search($request->query('q'))
            ->orderBy('nisn')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students', compact('students'));
    }

    public function store(StoreStudentRequest $request, BillingService $billing): RedirectResponse
    {
        $student = User::create($request->validated() + ['role' => User::ROLE_STUDENT, 'is_active' => true]);
        $billing->syncBills($student);

        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(UpdateStudentRequest $request, User $student, BillingService $billing): RedirectResponse
    {
        $data = $request->safe()->only(['nisn', 'name']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->filled('password')) {       // blank password = keep the old one
            $data['password'] = $request->input('password');
        }

        $student->update($data);

        if ($student->is_active) {
            $billing->syncBills($student);
        }

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Students are never deleted: their bills and transactions must stay traceable.
     * "Hapus" therefore deactivates the account (it can be re-activated via Edit).
     */
    public function destroy(User $student): RedirectResponse
    {
        if (! $student->is_active) {
            return back()->with('success', 'Siswa sudah nonaktif.');
        }

        $student->update(['is_active' => false]);

        return back()->with('success', 'Siswa dinonaktifkan. Data dan riwayat pembayaran tetap tersimpan.');
    }
}
