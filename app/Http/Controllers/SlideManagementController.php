<?php

namespace App\Http\Controllers;

use App\Models\LandingSetting;
use App\Models\LandingSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Manajemen slide "Tentang Kami" di landing page (khusus super admin).
 */
class SlideManagementController extends Controller
{
    public function index()
    {
        return view('admin.slides.index', [
            'slides' => LandingSlide::orderBy('sort_order')->orderBy('id')->get(),
            'interval' => max(2, min(30, (int) LandingSetting::read('slide_interval', 6))),
            'autoplay' => (string) LandingSetting::read('slide_autoplay', '1') === '1',
        ]);
    }

    public function create()
    {
        // Contoh isi awal agar pratinjau tidak kosong.
        $slide = new LandingSlide([
            'is_active' => true,
            'image_position' => 'center',
            'image_fit' => 'cover',
            'bg_color' => '#bcdcee',
            'card' => [
                'label' => ['text' => 'Label kartu'],
                'title' => ['text' => 'Judul atau pesan utama pada kartu.'],
                'subtitle' => ['text' => 'Keterangan tambahan'],
            ],
            'copy' => [
                'eyebrow' => ['text' => 'Judul kecil'],
                'title' => ['text' => "Judul slide\nyang *menarik.*"],
                'body' => ['text' => 'Tulis isi penjelasan slide di sini.'],
            ],
        ]);

        return view('admin.slides.form', ['slide' => $slide, 'isNew' => true]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());

        $slide = new LandingSlide(LandingSlide::sanitizeBasics($request->all()) + [
            'card' => LandingSlide::sanitizeCard($request->input('card')),
            'copy' => LandingSlide::sanitizeCopy($request->input('copy')),
        ]);
        $slide->sort_order = (int) LandingSlide::max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $slide->image_path = $request->file('image')->store('landing-slides', 'public');
        }

        $slide->save();

        flash()->success('Berhasil', 'Slide baru berhasil ditambahkan.');

        return redirect()->route('slides.index');
    }

    public function edit($id)
    {
        return view('admin.slides.form', ['slide' => LandingSlide::findOrFail($id), 'isNew' => false]);
    }

    public function update(Request $request, $id)
    {
        $slide = LandingSlide::findOrFail($id);

        $request->validate($this->rules(), $this->messages());

        $slide->fill(LandingSlide::sanitizeBasics($request->all()) + [
            'card' => LandingSlide::sanitizeCard($request->input('card')),
            'copy' => LandingSlide::sanitizeCopy($request->input('copy')),
        ]);

        if ($request->hasFile('image')) {
            $this->deleteUploadedImage($slide);
            $slide->image_path = $request->file('image')->store('landing-slides', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteUploadedImage($slide);
            $slide->image_path = null;
        }

        $slide->save();

        flash()->success('Berhasil', 'Slide berhasil diperbarui.');

        return redirect()->route('slides.index');
    }

    public function destroy($id)
    {
        $slide = LandingSlide::findOrFail($id);

        $this->deleteUploadedImage($slide);
        $slide->delete();

        flash()->success('Berhasil', 'Slide berhasil dihapus.');

        return redirect()->route('slides.index');
    }

    public function toggle($id)
    {
        $slide = LandingSlide::findOrFail($id);
        $slide->is_active = ! $slide->is_active;
        $slide->save();

        flash()->success('Berhasil', $slide->is_active ? 'Slide ditampilkan di landing page.' : 'Slide disembunyikan dari landing page.');

        return redirect()->route('slides.index');
    }

    public function move($id, $direction)
    {
        $slides = LandingSlide::orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $slides->search(fn ($s) => (int) $s->id === (int) $id);

        if ($index === false) {
            abort(404);
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (isset($slides[$target])) {
            $ordered = $slides->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

            foreach ($ordered as $position => $item) {
                if ((int) $item->sort_order !== $position + 1) {
                    $item->sort_order = $position + 1;
                    $item->save();
                }
            }
        }

        return redirect()->route('slides.index');
    }

    public function settings(Request $request)
    {
        $request->validate([
            'slide_interval' => ['required', 'integer', 'min:2', 'max:30'],
        ], [
            'slide_interval.required' => 'Kecepatan pergantian slide wajib diisi.',
            'slide_interval.integer' => 'Kecepatan pergantian slide harus berupa angka bulat.',
            'slide_interval.min' => 'Kecepatan pergantian slide minimal 2 detik.',
            'slide_interval.max' => 'Kecepatan pergantian slide maksimal 30 detik.',
        ]);

        LandingSetting::write('slide_interval', (int) $request->input('slide_interval'));
        LandingSetting::write('slide_autoplay', $request->boolean('slide_autoplay') ? '1' : '0');

        flash()->success('Berhasil', 'Pengaturan pergantian slide disimpan.');

        return redirect()->route('slides.index');
    }

    private function rules(): array
    {
        return [
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'image_alt' => ['nullable', 'string', 'max:200'],
        ];
    }

    private function messages(): array
    {
        return [
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar maksimal 3 MB.',
            'image.file' => 'Gambar gagal diunggah. Coba lagi.',
            'image_alt.max' => 'Teks alternatif gambar terlalu panjang (maksimal 200 karakter).',
        ];
    }

    /** Hapus berkas unggahan; gambar bawaan di public/images tidak disentuh. */
    private function deleteUploadedImage(LandingSlide $slide): void
    {
        if ($slide->hasUploadedImage() && Storage::disk('public')->exists($slide->image_path)) {
            Storage::disk('public')->delete($slide->image_path);
        }
    }
}
