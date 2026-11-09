<?php

namespace App\Http\Controllers;

use App\Models\FinalReport;
use App\Models\MonthlyReport;
use App\Models\Division;
use App\Models\Mentor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $history = collect();

        if ($user) {
            $monthlyHistory = MonthlyReport::where('user_id', $user->id)
                ->orderBy('report_month', 'desc')
                ->get()
                ->map(function ($report) {
                    return [
                        'type' => 'Laporan Bulanan',
                        'period' => $report->report_month,
                        'date' => optional($report->created_at)->format('d-m-Y H:i'),
                        'sort_key' => optional($report->created_at)->timestamp ?: 0,
                    ];
                });

            $finalHistory = FinalReport::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($report) {
                    return [
                        'type' => 'Laporan Akhir',
                        'period' => optional($report->created_at)->format('Y-m'),
                        'date' => optional($report->created_at)->format('d-m-Y H:i'),
                        'sort_key' => optional($report->created_at)->timestamp ?: 0,
                    ];
                });

            $history = $monthlyHistory->merge($finalHistory)->sortByDesc('sort_key')->values();
        }

        return view('user.profile')->with([
            'user' => $user,
            'history' => $history,
            'divisions' => Division::with(['mentors' => function ($q) {
                $q->orderBy('name');
            }])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:500'],
            'birth_date' => ['required', 'date'],
            'institution' => ['required', 'string', 'max:255'],
            'internship_start_date' => ['nullable', 'date'],
            'internship_end_date' => ['nullable', 'date', 'after_or_equal:internship_start_date'],
            'division_id' => ['required', 'exists:divisions,id'],
            'major' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:25'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'is_discoverable' => ['nullable', 'boolean'],
            'card_visibility' => ['nullable', 'array'],
            'card_visibility.*' => ['nullable', 'boolean'],
        ]);

        $oldEmail = $user->email;
        $division = Division::find($request->division_id);
        $mentor = Mentor::where('division_id', $request->division_id)->orderBy('name')->first();

        $user->name = $request->name;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->birth_date = $request->birth_date;
        $user->institution = $request->institution;
        $user->division_id = $division ? $division->id : null;
        $user->mentor_id = $mentor ? $mentor->id : null;
        $user->internship_start_date = $request->internship_start_date;
        $user->internship_end_date = $request->internship_end_date;
        $user->email = $request->email;
        $user->emergency_contact_name = $request->emergency_contact_name;
        $user->emergency_contact_phone = $request->emergency_contact_phone;
        $user->emergency_contact_relation = $request->emergency_contact_relation;
        $user->bank_name = $request->bank_name;
        $user->bank_account_number = $request->bank_account_number;
        $user->major = $request->major;

        // Pengaturan direktori: bisa ditemukan atau tidak + isian yang tampil di kartu.
        $user->is_discoverable = $request->boolean('is_discoverable');
        $cardInput = (array) $request->input('card_visibility', []);
        $visibility = [];
        foreach (array_keys(\App\Models\User::CARD_FIELDS) as $key) {
            $visibility[$key] = filter_var($cardInput[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        $user->card_visibility = $visibility;

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->profile_photo_path = $request->file('profile_photo')->store('profile-photos', 'public');
        } elseif ($request->boolean('remove_photo')) {
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->profile_photo_path = null;
        }
        $user->save();


        flash()->success('Berhasil', 'Profil berhasil diperbarui.');
        return redirect()->route('user.profile');
    }
}
