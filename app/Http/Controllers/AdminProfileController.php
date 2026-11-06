<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load('division');

        return view('admin.profile')->with([
            'user' => $user,
            'divisions' => Division::orderBy('name')->get(),
            'roleLabel' => $user->hasRole('super_admin') ? 'Super Administrator' : ($user->isMentor() ? 'Mentor' : 'Administrator'),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format :attribute tidak valid.',
            'unique' => ':attribute sudah digunakan akun lain.',
            'exists' => ':attribute tidak valid.',
            'profile_photo.max' => 'Ukuran foto maksimal 2 MB.',
            'profile_photo.image' => 'Foto profil harus berupa gambar.',
            'profile_photo.mimes' => 'Foto profil harus berformat JPG, PNG, atau WebP.',
        ], [
            'name' => 'Nama lengkap',
            'email' => 'Email',
            'division_id' => 'Divisi',
            'profile_photo' => 'Foto profil',
        ]);

        $user->name = trim($data['name']);
        $user->email = trim($data['email']);
        if (! $user->isMentor()) {
            $user->division_id = $data['division_id'] ?? null;
        }

        if ($request->hasFile('profile_photo')) {
            $this->deletePhoto($user->profile_photo_path);
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        $user->save();

        flash()->success('Berhasil', 'Profil berhasil diperbarui.');

        return redirect()->route('admin.profile');
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
