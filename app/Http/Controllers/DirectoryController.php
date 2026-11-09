<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\User;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    /** Halaman Direktori Peserta (tampilan menyesuaikan peran: portal peserta atau panel admin/mentor). */
    public function index(Request $request)
    {
        $view = auth()->user()->hasAnyRole(['admin', 'mentor']) ? 'admin.directory' : 'user.directory';

        return view($view, [
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'q' => trim((string) $request->query('q', '')),
        ]);
    }

    /**
     * Pencarian peserta (JSON). Dipakai kolom cari di navbar dan halaman direktori.
     * Semua pihak (termasuk admin/mentor) melihat kartu dari sudut pandang peserta lain,
     * jadi isian yang disembunyikan pemiliknya tidak pernah ikut dikirim.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('limit', 24), 1), 48);

        $query = User::discoverable()->with(['division', 'mentor']);

        if ($q !== '') {
            $query->where('users.name', 'like', '%' . addcslashes($q, '\\%_') . '%');
        }

        if ($request->filled('division')) {
            $query->where('users.division_id', (int) $request->query('division'));
        }

        $page = $query->orderBy('users.name')->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(function (User $user) {
                return $user->directoryCard();
            })->values(),
            'total' => $page->total(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }
}
