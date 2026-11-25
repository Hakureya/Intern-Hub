<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\WorkCategory;
use App\Models\WorkTask;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kategori tugas harian per divisi.
 * Mentor: hanya divisinya. Admin / super admin: seluruh divisi.
 */
class WorkCategoryController extends Controller
{
    private function authorizeDivision($divisionId): void
    {
        $u = auth()->user();
        abort_unless($u && $u->hasAnyRole(['admin', 'mentor']), 403);
        if ($u->isMentor()) {
            abort_unless($divisionId && (int) $u->division_id === (int) $divisionId, 403);
        } else {
            abort_unless(Division::whereKey($divisionId)->exists(), 404);
        }
    }

    private function usage(WorkCategory $c): int
    {
        return WorkTask::where('type', WorkTask::TYPE_DAILY)->where('category', $c->name)
            ->whereHas('assignees.user', function ($q) use ($c) {
                $q->where('users.division_id', $c->division_id);
            })->count();
    }

    private function payload($divisionId): array
    {
        $u = auth()->user();
        WorkCategory::seedDefaults($divisionId);

        return [
            'division_id' => (int) $divisionId,
            'divisions' => $u->isMentor() ? [] : Division::orderBy('name')->get(['id', 'name'])->all(),
            'categories' => WorkCategory::where('division_id', $divisionId)->orderBy('name')->get()->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->name, 'uses' => $this->usage($c)];
            })->all(),
        ];
    }

    public function index(Request $request)
    {
        $u = auth()->user();
        $divisionId = $u->isMentor() ? $u->division_id : ((int) $request->input('division_id') ?: optional(Division::orderBy('name')->first())->id);
        $this->authorizeDivision($divisionId);

        return response()->json($this->payload($divisionId));
    }

    public function store(Request $request)
    {
        $divisionId = (int) $request->input('division_id');
        $this->authorizeDivision($divisionId);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', Rule::unique('work_categories', 'name')->where('division_id', $divisionId)],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 40 karakter.',
            'name.unique' => 'Kategori ini sudah ada di divisi tersebut.',
        ]);

        WorkCategory::create(['division_id' => $divisionId, 'name' => trim($data['name'])]);

        return response()->json($this->payload($divisionId));
    }

    public function update(Request $request, WorkCategory $category)
    {
        $this->authorizeDivision($category->division_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:40', Rule::unique('work_categories', 'name')->where('division_id', $category->division_id)->ignore($category->id)],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 40 karakter.',
            'name.unique' => 'Kategori ini sudah ada di divisi tersebut.',
        ]);

        $old = $category->name;
        $new = trim($data['name']);

        // Tugas harian divisi ini yang memakai nama lama ikut diganti.
        WorkTask::where('type', WorkTask::TYPE_DAILY)->where('category', $old)
            ->whereHas('assignees.user', function ($q) use ($category) {
                $q->where('users.division_id', $category->division_id);
            })->update(['category' => $new]);

        $category->update(['name' => $new]);

        return response()->json($this->payload($category->division_id));
    }

    public function destroy(WorkCategory $category)
    {
        $this->authorizeDivision($category->division_id);
        $divisionId = $category->division_id;
        // Tugas lama tetap menyimpan nama kategorinya; kategori hanya hilang dari pilihan dropdown.
        $category->delete();

        return response()->json($this->payload($divisionId));
    }
}
