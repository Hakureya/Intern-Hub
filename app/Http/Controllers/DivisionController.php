<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = Division::orderBy('name')->get();
        return view('admin.divisions')->with(['divisions' => $divisions]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name',
        ]);

        Division::create([
            'name' => trim((string) $request->name),
        ]);

        flash()->success('Berhasil', 'Divisi berhasil ditambahkan.');
        return back();
    }

    public function update(Request $request, Division $division)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name,' . $division->id,
        ]);

        $division->name = trim((string) $request->name);
        $division->save();

        flash()->success('Berhasil', 'Divisi berhasil diperbarui.');
        return back();
    }

    public function destroy(Division $division)
    {
        $division->delete();
        flash()->success('Berhasil', 'Divisi berhasil dihapus.');
        return back();
    }
}
