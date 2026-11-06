<?php

namespace App\Http\Controllers;

use App\Models\InternshipDraftDocument;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function userDashboard(Request $request)
    {
        $category = (string) $request->query('category', 'all');
        $allowedCategories = ['all', 'compro', 'laporan_keuangan', 'pedoman_sop', 'materi_orientasi', 'lainnya'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'all';
        }

        $query = InternshipDraftDocument::query()
            ->with('uploader')
            ->where('document_type', 'dossier')
            ->orderByDesc('created_at');

        if ($category !== 'all') {
            $query->where('library_category', $category);
        }

        $documents = $query->get();

        return view('user.home', [
            'documents' => $documents,
            'selectedCategory' => $category,
        ]);
    }
}
