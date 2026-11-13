<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;

class AnnouncementController extends Controller
{
    public function store(AnnouncementRequest $request): RedirectResponse
    {
        Announcement::create($request->validated() + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Pengumuman berhasil dibuat dan tampil di dashboard siswa.');
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }
}
