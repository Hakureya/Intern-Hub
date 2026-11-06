<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Mentor;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function index()
    {
        $mentors = Mentor::with('division')->orderBy('name')->get();
        $divisions = Division::orderBy('name')->get();
        return view('admin.mentors')->with([
            'mentors' => $mentors,
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:255',
        ]);

        Mentor::create([
            'division_id' => (int) $request->division_id,
            'name' => trim((string) $request->name),
        ]);

        flash()->success('Berhasil', 'Mentor berhasil ditambahkan.');
        return back();
    }

    public function update(Request $request, Mentor $mentor)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:255',
        ]);

        $mentor->division_id = (int) $request->division_id;
        $mentor->name = trim((string) $request->name);
        $mentor->save();

        flash()->success('Berhasil', 'Mentor berhasil diperbarui.');
        return back();
    }

    public function destroy(Mentor $mentor)
    {
        $mentor->delete();
        flash()->success('Berhasil', 'Mentor berhasil dihapus.');
        return back();
    }
}
