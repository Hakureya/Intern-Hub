<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskMessage;
use App\Models\WorkTaskReference;
use App\Support\TaskHistory;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Self-contained: memakai SQLite in-memory dengan skema minimal (users, roles, divisi + tabel tugas),
 * jadi tidak menyentuh database aplikasi.
 */
class WorkTaskDatesAndCollabTest extends TestCase
{
    private const BASE_WORK_MIGRATIONS = [
        '2026_11_05_000001_create_work_tasks_tables.php',
        '2026_11_06_000001_create_work_task_attachments_table.php',
        '2026_11_11_000001_add_worked_on_to_work_tasks_table.php',
        '2026_11_11_000002_create_work_task_references_table.php',
        '2026_11_12_000001_create_work_task_messages_tables.php',
        '2026_11_13_000001_add_uploaded_by_to_work_task_files.php',
    ];

    private const DATES_MIGRATION = '2026_11_14_000001_add_start_end_dates_to_work_tasks_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'session.driver' => 'array',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');

        $this->createBaseSchema();
        foreach (self::BASE_WORK_MIGRATIONS as $file) {
            $this->runMigration($file);
        }

        // Uji backfill menjalankan migrasi tanggal sendiri setelah menyisipkan data lama.
        if ($this->getName(false) !== 'test_migration_backfills_existing_tasks') {
            $this->runMigration(self::DATES_MIGRATION);
        }

        Storage::fake('public');
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /* ---------- helpers ---------- */

    protected function createBaseSchema(): void
    {
        Schema::create('divisions', function ($t) { $t->increments('id'); $t->string('name'); $t->timestamps(); });
        Schema::create('mentors', function ($t) { $t->increments('id'); $t->unsignedInteger('division_id')->nullable(); $t->string('name'); $t->timestamps(); });
        Schema::create('roles', function ($t) { $t->increments('id'); $t->string('slug')->unique(); $t->string('name'); $t->text('permissions')->nullable(); $t->timestamps(); });
        Schema::create('role_users', function ($t) { $t->unsignedInteger('user_id'); $t->unsignedInteger('role_id'); $t->primary(['user_id', 'role_id']); });
        Schema::create('users', function ($t) {
            $t->increments('id');
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_discoverable')->default(false);
            $t->text('card_visibility')->nullable();
            $t->string('phone_number')->nullable();
            $t->string('address')->nullable();
            $t->date('birth_date')->nullable();
            $t->string('institution')->nullable();
            $t->string('major')->nullable();
            $t->unsignedInteger('division_id')->nullable();
            $t->unsignedInteger('mentor_id')->nullable();
            $t->date('internship_start_date')->nullable();
            $t->date('internship_end_date')->nullable();
            $t->string('profile_photo_path')->nullable();
            $t->string('emergency_contact_name')->nullable();
            $t->string('emergency_contact_phone')->nullable();
            $t->string('emergency_contact_relation')->nullable();
            $t->string('bank_name')->nullable();
            $t->string('bank_account_number')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        foreach (['admin' => 'Admin', 'mentor' => 'Mentor', 'user' => 'Peserta'] as $slug => $name) {
            Role::create(['slug' => $slug, 'name' => $name]);
        }
    }

    protected function runMigration(string $file): void
    {
        // Lewat Migrator agar migrasi berkelas-bernama tidak dimuat dua kali antar test.
        $migrator = app('migrator');
        if (! $migrator->getRepository()->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }
        $migrator->run([database_path('migrations/' . $file)]);
    }

    protected function makeUser(string $role, string $name, ?int $divisionId = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.test',
            'password' => bcrypt('secret'),
            'division_id' => $divisionId,
        ]);
        $user->roles()->attach(Role::where('slug', $role)->first()->id);

        return $user;
    }

    protected function fixture(): array
    {
        $division = Division::create(['name' => 'Teknologi']);

        return [
            'mentor' => $this->makeUser('mentor', 'Mentor Satu', $division->id),
            'a' => $this->makeUser('user', 'Peserta A', $division->id),
            'b' => $this->makeUser('user', 'Peserta B', $division->id),
            'c' => $this->makeUser('user', 'Peserta C', $division->id),
        ];
    }

    protected function selfTask(User $creator, array $partners = []): WorkTask
    {
        $today = Carbon::today()->toDateString();
        $task = WorkTask::create([
            'title' => 'Rekap data', 'description' => 'Deskripsi',
            'worked_on' => $today, 'start_date' => $today, 'end_date' => $today,
            'require_attachment' => false, 'is_self' => true, 'created_by' => $creator->id,
        ]);
        foreach (array_merge([$creator], $partners) as $u) {
            WorkTaskAssignee::create(['work_task_id' => $task->id, 'user_id' => $u->id, 'status' => 'menunggu']);
        }

        return $task;
    }

    protected function mentorTask(User $mentor, array $assignees, ?string $deadline = null): WorkTask
    {
        $deadline = $deadline ?: Carbon::today()->addDays(7)->toDateString();
        $task = WorkTask::create([
            'title' => 'Dokumentasi', 'description' => 'Deskripsi', 'deadline' => $deadline,
            'start_date' => Carbon::today()->toDateString(), 'end_date' => $deadline,
            'require_attachment' => false, 'is_self' => false, 'created_by' => $mentor->id,
        ]);
        foreach ($assignees as $u) {
            WorkTaskAssignee::create(['work_task_id' => $task->id, 'user_id' => $u->id, 'status' => 'belum']);
        }

        return $task;
    }

    /* ---------- 1. tanggal mulai & selesai ---------- */

    public function test_mentor_must_fill_deadline_and_dates_are_derived(): void
    {
        $f = $this->fixture();
        $payload = ['title' => 'Tugas baru', 'description' => 'Isi', 'require_attachment' => 0, 'participants' => [$f['a']->id]];

        $this->actingAs($f['mentor'])->post(route('work-logs.store'), $payload)->assertSessionHasErrors('deadline');
        $this->assertSame(0, WorkTask::count());

        $deadline = Carbon::today()->addDays(5)->toDateString();
        $this->actingAs($f['mentor'])->post(route('work-logs.store'), $payload + ['deadline' => $deadline])->assertSessionHasNoErrors();

        $task = WorkTask::first();
        $this->assertSame(Carbon::today()->toDateString(), $task->start_date->toDateString());
        $this->assertSame($deadline, $task->end_date->toDateString());
        $this->assertSame($deadline, $task->deadline->toDateString());
    }

    public function test_mentor_update_requires_deadline_and_syncs_end_date(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']]);
        $base = ['title' => 'Dokumentasi', 'description' => 'Deskripsi', 'require_attachment' => 0, 'participants' => [$f['a']->id]];

        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), $base)->assertSessionHasErrors('deadline');

        $new = Carbon::today()->addDays(10)->toDateString();
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), $base + ['deadline' => $new])->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame($new, $task->end_date->toDateString());
        $this->assertSame($new, $task->deadline->toDateString());
        $this->assertSame(Carbon::today()->toDateString(), $task->start_date->toDateString(), 'tanggal mulai tetap');

        // tenggat yang diubah ke masa lalu ditolak
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), $base + ['deadline' => Carbon::yesterday()->toDateString()])->assertSessionHasErrors('deadline');
    }

    public function test_intern_can_log_task_with_start_and_end_date(): void
    {
        $f = $this->fixture();
        $start = Carbon::today()->subDays(3)->toDateString();
        $end = Carbon::today()->subDay()->toDateString();
        $payload = ['title' => 'Catatan', 'description' => 'Isi'];

        // selesai sebelum mulai
        $this->actingAs($f['a'])->post(route('user.work-logs.store'), $payload + ['start_date' => $end, 'end_date' => $start])->assertSessionHasErrors('end_date');
        // tanggal selesai di masa depan
        $this->actingAs($f['a'])->post(route('user.work-logs.store'), $payload + ['start_date' => $start, 'end_date' => Carbon::tomorrow()->toDateString()])->assertSessionHasErrors('end_date');
        // tanpa tanggal
        $this->actingAs($f['a'])->post(route('user.work-logs.store'), $payload)->assertSessionHasErrors(['start_date', 'end_date']);

        $this->actingAs($f['a'])->post(route('user.work-logs.store'), $payload + ['start_date' => $start, 'end_date' => $end])->assertSessionHasNoErrors();

        $task = WorkTask::first();
        $this->assertSame($start, $task->start_date->toDateString());
        $this->assertSame($end, $task->end_date->toDateString());
        $this->assertTrue($task->is_self);
        $this->assertNull($task->deadline);
    }

    public function test_migration_backfills_existing_tasks(): void
    {
        $f = $this->fixture();
        $now = now()->toDateTimeString();
        $legacy = function (int $id, string $title, array $extra) use ($now) {
            return $extra + ['id' => $id, 'title' => $title, 'description' => 'x', 'deadline' => null, 'worked_on' => null,
                'is_self' => 0, 'created_by' => 1, 'created_at' => '2026-11-05 09:00:00', 'updated_at' => $now];
        };
        DB::table('work_tasks')->insert([
            $legacy(1, 'Mentor ber-tenggat', ['deadline' => '2026-11-20', 'created_by' => $f['mentor']->id]),
            $legacy(2, 'Mentor tanpa tenggat', ['created_at' => '2026-11-06 09:00:00', 'created_by' => $f['mentor']->id]),
            $legacy(3, 'Mandiri', ['worked_on' => '2026-11-02', 'is_self' => 1, 'created_at' => '2026-11-04 09:00:00', 'created_by' => $f['a']->id]),
        ]);

        $this->runMigration(self::DATES_MIGRATION);

        $rows = DB::table('work_tasks')->orderBy('id')->get()->keyBy('id');
        $this->assertSame(['2026-11-05', '2026-11-20'], [$rows[1]->start_date, $rows[1]->end_date]);
        $this->assertSame(['2026-11-06', null], [$rows[2]->start_date, $rows[2]->end_date]);
        $this->assertSame(['2026-11-02', '2026-11-02'], [$rows[3]->start_date, $rows[3]->end_date]);
    }

    /* ---------- 2a. rekan collab bisa mengedit ---------- */

    public function test_collaborator_can_edit_task_created_by_partner_but_outsider_cannot(): void
    {
        $f = $this->fixture();
        $task = $this->selfTask($f['a'], [$f['b']]);

        $this->assertTrue($task->canBeEditedBy($f['a']));
        $this->assertTrue($task->canBeEditedBy($f['b']), 'rekan collab boleh mengedit');
        $this->assertFalse($task->canBeEditedBy($f['c']), 'bukan anggota tidak boleh');

        $payload = ['title' => 'Judul baru dari rekan', 'description' => 'Deskripsi', 'start_date' => Carbon::today()->subDay()->toDateString(), 'end_date' => Carbon::today()->toDateString()];

        $this->actingAs($f['b'])->put(route('user.work-logs.update', $task), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Judul baru dari rekan', $task->fresh()->title);

        // riwayat mencatat nama rekan sebagai pengubah
        $edit = WorkTaskMessage::where('kind', WorkTaskMessage::EDIT)->latest('id')->first();
        $this->assertSame($f['b']->id, (int) $edit->user_id);
        $labels = array_column($edit->changes, 'label');
        $this->assertContains('Judul', $labels);
        $this->assertContains('Tanggal mulai', $labels);

        // pihak luar tidak bisa melihat maupun mengedit
        $this->actingAs($f['c'])->put(route('user.work-logs.update', $task), $payload)->assertForbidden();
        $this->actingAs($f['c'])->get(route('user.work-logs.edit', $task))->assertForbidden();
    }

    public function test_pending_partner_request_does_not_grant_edit(): void
    {
        $f = $this->fixture();
        $task = $this->selfTask($f['a']);
        \App\Models\WorkTaskPartnerRequest::create(['work_task_id' => $task->id, 'from_user_id' => $f['a']->id, 'to_user_id' => $f['b']->id, 'status' => 'pending']);

        $this->assertFalse($task->fresh()->canBeEditedBy($f['b']));
    }

    /* ---------- 2b. peserta menambah referensi ---------- */

    public function test_intern_can_add_and_remove_own_references(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']]);
        $mentorRef = WorkTaskReference::create(['work_task_id' => $task->id, 'type' => 'link', 'name' => 'Panduan', 'url' => 'https://example.test/guide', 'uploaded_by' => $f['mentor']->id]);

        // tautan
        $this->actingAs($f['a'])->post(route('user.work-logs.references.store', $task), ['reference_link' => 'https://example.test/materi', 'reference_link_label' => 'Materi A'])->assertSessionHasNoErrors();
        // file
        $this->actingAs($f['a'])->post(route('user.work-logs.references.store', $task), ['reference_files' => [UploadedFile::fake()->create('catatan.pdf', 50, 'application/pdf')]])->assertSessionHasNoErrors();

        $mine = $task->references()->where('uploaded_by', $f['a']->id)->get();
        $this->assertCount(2, $mine);
        Storage::disk('public')->assertExists($mine->firstWhere('type', 'file')->path);

        // status penyelesaian tidak ikut berubah
        $this->assertSame('belum', $task->assignees()->first()->status);

        // validasi
        $this->actingAs($f['a'])->post(route('user.work-logs.references.store', $task), [])->assertSessionHasErrors('reference_files');
        $this->actingAs($f['a'])->post(route('user.work-logs.references.store', $task), ['reference_link' => 'bukan-url'])->assertSessionHasErrors('reference_link');

        // tidak bisa menghapus referensi mentor, bisa menghapus miliknya
        $this->actingAs($f['a'])->delete(route('user.work-logs.references.destroy', [$task, $mentorRef]))->assertForbidden();
        $own = $mine->firstWhere('type', 'file');
        $this->actingAs($f['a'])->delete(route('user.work-logs.references.destroy', [$task, $own]))->assertSessionHasNoErrors();
        $this->assertNull(WorkTaskReference::find($own->id));
        Storage::disk('public')->assertMissing($own->path);

        // peserta lain (bukan pengerja tugas ini) ditolak
        $this->actingAs($f['b'])->post(route('user.work-logs.references.store', $task), ['reference_link' => 'https://example.test/x'])->assertForbidden();

        // tercatat di riwayat
        $notes = WorkTaskMessage::where('kind', WorkTaskMessage::EDIT)->get()->pluck('changes')->flatten(1)->pluck('note')->filter()->implode(' | ');
        $this->assertStringContainsString('Menambah: Materi A', $notes);
        $this->assertStringContainsString('Menghapus: catatan.pdf', $notes);
    }

    /* ---------- 3. riwayat hanya di popup ---------- */

    public function test_edit_history_is_not_in_chat_but_in_history_popup(): void
    {
        $f = $this->fixture();
        $task = $this->selfTask($f['a']);

        TaskHistory::record($task, $f['a'], [TaskHistory::change('Judul', 'Lama', 'Baru')]);
        WorkTaskMessage::create(['work_task_id' => $task->id, 'user_id' => $f['a']->id, 'kind' => WorkTaskMessage::TEXT, 'body' => 'Halo tim']);

        $chat = $this->actingAs($f['a'])->getJson(route('work-logs.chat.index', $task))->assertOk()->json('messages');
        $this->assertCount(1, $chat);
        $this->assertSame('text', $chat[0]['kind']);

        // polling pesan baru juga tidak membawa catatan edit
        $poll = $this->actingAs($f['a'])->getJson(route('work-logs.chat.index', ['task' => $task, 'after' => 0, 'since' => now()->subDay()->toIso8601String()]))->json('messages');
        $this->assertSame(['text'], array_values(array_unique(array_column($poll, 'kind'))));

        $history = $this->actingAs($f['a'])->getJson(route('work-logs.history', $task))->assertOk()->json('messages');
        $this->assertCount(1, $history);
        $this->assertSame('edit', $history[0]['kind']);
    }

    public function test_chat_accepts_several_attachments_with_one_caption(): void
    {
        $f = $this->fixture();
        $task = $this->selfTask($f['a']);
        $url = route('work-logs.chat.store', $task);

        $res = $this->actingAs($f['a'])->postJson($url, [
            'body' => 'Foto dan dokumen kegiatan',
            'files' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.png'),
                UploadedFile::fake()->create('c.mp4', 500, 'video/mp4'),
                UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
            ],
        ])->assertCreated();

        $res->assertJsonPath('message.body', 'Foto dan dokumen kegiatan');
        $this->assertSame(['image', 'image', 'video', 'file'], array_column($res->json('message.files'), 'type'));
        $this->assertSame(1, WorkTaskMessage::where('kind', WorkTaskMessage::TEXT)->count(), 'satu pesan, bukan satu per file');

        // lampiran saja tanpa keterangan tetap boleh
        $this->actingAs($f['a'])->postJson($url, ['files' => [UploadedFile::fake()->image('d.jpg')]])->assertCreated();

        // lebih dari 10 file ditolak
        $many = [];
        for ($i = 0; $i < 11; $i++) { $many[] = UploadedFile::fake()->image("f$i.jpg"); }
        $this->actingAs($f['a'])->postJson($url, ['files' => $many])->assertStatus(422);

        // format tidak didukung ditolak
        $this->actingAs($f['a'])->postJson($url, ['files' => [UploadedFile::fake()->create('x.exe', 10)]])->assertStatus(422);
    }

    /* ---------- mentor bisa mengedit tugas lewat tenggat & tugas yang dicatat peserta ---------- */

    public function test_mentor_can_edit_past_deadline_task(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']], Carbon::yesterday()->toDateString());
        $task->update(['start_date' => Carbon::today()->subDays(10)->toDateString()]);
        WorkTaskAssignee::where('work_task_id', $task->id)->update(['status' => 'sudah']);

        $this->assertTrue($task->fresh()->isPastDeadline());
        $this->assertTrue($task->fresh()->canBeEditedBy($f['mentor']), 'mentor boleh walau lewat tenggat');
        $this->actingAs($f['mentor'])->get(route('work-logs.edit', $task))->assertOk();

        // tenggat dibiarkan sama (sudah lewat) -> tetap bisa disimpan; tenggat diperpanjang -> juga bisa
        $payload = ['title' => 'Judul revisi', 'description' => 'Deskripsi', 'require_attachment' => 0, 'participants' => [$f['a']->id], 'deadline' => Carbon::yesterday()->toDateString()];
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Judul revisi', $task->fresh()->title);

        $extended = Carbon::today()->addDays(3)->toDateString();
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), ['deadline' => $extended] + $payload)->assertSessionHasNoErrors();
        $this->assertSame($extended, $task->fresh()->end_date->toDateString());
        $this->assertFalse($task->fresh()->isPastDeadline());

        // peserta tetap tidak bisa mengedit tugas dari mentor
        $this->assertFalse($task->fresh()->canBeEditedBy($f['a']));
    }

    public function test_mentor_can_edit_task_recorded_by_intern(): void
    {
        $f = $this->fixture();
        $task = $this->selfTask($f['a'], [$f['b']]);
        $task->update(['deadline' => Carbon::yesterday()->toDateString()]); // bahkan bila (dulu) sudah lewat tenggat
        WorkTaskAssignee::where('work_task_id', $task->id)->update(['status' => 'sudah']);

        $this->assertTrue($task->fresh()->canBeEditedBy($f['mentor']));

        $page = $this->actingAs($f['mentor'])->get(route('work-logs.edit', $task))->assertOk()->getContent();
        $this->assertStringContainsString('name="start_date"', $page);
        $this->assertStringContainsString('name="end_date"', $page);
        $this->assertStringNotContainsString('name="deadline"', $page);
        $this->assertStringNotContainsString('name="participants[]"', $page);

        $start = Carbon::today()->subDays(4)->toDateString();
        $end = Carbon::today()->subDays(2)->toDateString();
        $payload = ['title' => 'Rekap data (dikoreksi)', 'description' => 'Deskripsi baru', 'start_date' => $start, 'end_date' => $end];

        // tanggal tidak valid
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), ['start_date' => $end, 'end_date' => $start] + $payload)->assertSessionHasErrors('end_date');
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), ['end_date' => Carbon::tomorrow()->toDateString()] + $payload)->assertSessionHasErrors('end_date');

        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), $payload)->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame('Rekap data (dikoreksi)', $task->title);
        $this->assertSame($start, $task->start_date->toDateString());
        $this->assertSame($end, $task->end_date->toDateString());
        $this->assertTrue($task->is_self, 'tetap tugas yang dicatat peserta');
        $this->assertSame(2, $task->assignees()->count(), 'peserta tidak berubah');
        $this->assertSame(['belum'], $task->assignees()->pluck('status')->unique()->values()->all(), 'status kembali belum');

        $edit = WorkTaskMessage::where('kind', WorkTaskMessage::EDIT)->latest('id')->first();
        $this->assertSame($f['mentor']->id, (int) $edit->user_id);
        $this->assertContains('Tanggal mulai', array_column($edit->changes, 'label'));
    }

    /* ---------- filter status (mentor) & pencarian (peserta) ---------- */

    public function test_mentor_index_status_filter(): void
    {
        $f = $this->fixture();
        $today = Carbon::today()->toDateString();
        $t1 = $this->mentorTask($f['mentor'], [$f['a']], $today); $t1->update(['title' => 'Tugas Belum']);
        $t2 = $this->mentorTask($f['mentor'], [$f['a']], $today); $t2->update(['title' => 'Tugas Menunggu']);
        $t3 = $this->mentorTask($f['mentor'], [$f['a'], $f['b']], $today); $t3->update(['title' => 'Tugas Campuran']);
        WorkTaskAssignee::where('work_task_id', $t2->id)->update(['status' => 'menunggu']);
        WorkTaskAssignee::where('work_task_id', $t3->id)->where('user_id', $f['a']->id)->update(['status' => 'sudah']);

        $month = Carbon::today()->format('Y-m');
        $titles = function ($res) { return $res->viewData('rows')->map(function ($r) { return $r['task']->title; })->sort()->values()->all(); };

        $all = $this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month]))->assertOk();
        $this->assertSame(['semua' => 3, 'sudah' => 1, 'menunggu' => 1, 'belum' => 2, 'batal' => 0], $all->viewData('stats'));
        $this->assertCount(3, $titles($all));
        foreach (['Semua tugas', 'Sudah selesai', 'Menunggu persetujuan', 'Belum dikerjakan'] as $label) {
            $all->assertSee($label, false);
        }

        $this->assertSame(['Tugas Menunggu'], $titles($this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month, 'status' => 'menunggu']))));
        $this->assertSame(['Tugas Campuran'], $titles($this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month, 'status' => 'sudah']))));
        $this->assertSame(['Tugas Belum', 'Tugas Campuran'], $titles($this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month, 'status' => 'belum']))));
        // status ngawur diabaikan
        $this->assertCount(3, $titles($this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month, 'status' => 'xyz']))));
        // dipadukan dengan pencarian: hitungan ringkasan mengikuti pencarian
        $q = $this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month, 'q' => 'Campuran']));
        $this->assertSame(['semua' => 1, 'sudah' => 1, 'menunggu' => 0, 'belum' => 1, 'batal' => 0], $q->viewData('stats'));
    }

    public function test_intern_index_search(): void
    {
        $f = $this->fixture();
        $today = Carbon::today()->toDateString();
        $this->selfTask($f['a'])->update(['title' => 'Rekap data penjualan', 'description' => 'excel']);
        $m = $this->mentorTask($f['mentor'], [$f['a']], $today); $m->update(['title' => 'Dokumentasi API', 'description' => 'swagger dan postman']);
        $month = Carbon::today()->format('Y-m');
        $titles = function ($res) { return $res->viewData('rows')->map(function ($r) { return $r['task']->title; })->sort()->values()->all(); };

        $res = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]))->assertOk();
        $this->assertCount(2, $titles($res));
        $res->assertSee('name="q"', false);

        $this->assertSame(['Rekap data penjualan'], $titles($this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month, 'q' => 'rekap']))));
        $this->assertSame(['Dokumentasi API'], $titles($this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month, 'q' => 'postman']))), 'cari juga di deskripsi');
        $none = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month, 'q' => 'tidak-ada']))->assertOk();
        $this->assertCount(0, $titles($none));
        $none->assertSee('Tidak ada tugas yang cocok', false);

        // ringkasan status mengikuti pencarian
        $s = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month, 'q' => 'API']));
        $this->assertSame(1, array_sum($s->viewData('stats')));
    }

    /* ---------- tampilan detail ---------- */

    public function test_detail_pages_share_the_same_layout(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']]);

        $intern = $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->assertOk()->getContent();
        $mentor = $this->actingAs($f['mentor'])->get(route('work-logs.show', $task))->assertOk()->getContent();

        foreach (['Tanggal mulai', 'Tanggal selesai', 'Dibuat pada', 'Dibuat oleh', 'Bukti pengerjaan', 'Opsional', 'Instruksi', 'Referensi Tugas', 'Hasil pekerjaan'] as $needle) {
            $this->assertStringContainsString($needle, $intern, "peserta: $needle");
            $this->assertStringContainsString($needle, $mentor, "mentor: $needle");
        }
        $this->assertStringContainsString('Mentor Satu', $intern, 'peserta melihat nama pembuat');
        $this->assertStringNotContainsString('Pekerjaanmu', $intern);
        $this->assertStringNotContainsString('Boleh mengumpulkan lebih dari satu lampiran', $intern);
        $this->assertStringNotContainsString('wl-assign-meta', $intern);
        $this->assertStringNotContainsString('Hasil pengerjaan', $mentor);
        $this->assertStringNotContainsString('Deskripsi</h4>', $mentor);
    }

    /* ---------- status batal ---------- */

    private function historyText(WorkTask $task): string
    {
        return WorkTaskMessage::where('work_task_id', $task->id)->where('kind', WorkTaskMessage::EDIT)->orderBy('id')->get()
            ->map(function ($m) { return $m->user_id . ':' . implode('|', array_map(function ($c) { return $c['label'] . '=' . ($c['old'] ?? '') . '>' . ($c['new'] ?? $c['note'] ?? ''); }, $m->changes)); })
            ->implode("\n");
    }

    public function test_mentor_cancels_task_hidden_from_interns_but_kept_for_mentor(): void
    {
        $f = $this->fixture();
        $month = Carbon::today()->format('Y-m');
        $task = $this->mentorTask($f['mentor'], [$f['a'], $f['b']], Carbon::today()->toDateString());
        $task->update(['title' => 'Tugas dibatalkan']);
        $other = $this->mentorTask($f['mentor'], [$f['a']], Carbon::today()->toDateString());
        $other->update(['title' => 'Tugas lain']);

        // konfirmasi tersedia di halaman (popup)
        $page = $this->actingAs($f['mentor'])->get(route('work-logs.show', $task))->assertOk()->getContent();
        $this->assertStringContainsString(route('work-logs.cancel', $task), $page);
        $this->assertStringContainsString('data-confirm-title="Batalkan tugas?"', $page);
        $this->assertStringContainsString('data-wl-cancel-confirm=', $page);
        $this->assertStringContainsString('value="batal"', $page, 'opsi Batal di pilihan status');

        $this->actingAs($f['mentor'])->post(route('work-logs.cancel', $task), ['review_note' => 'Proyek dihentikan'])->assertSessionHasNoErrors();
        $this->assertSame(['batal'], $task->assignees()->pluck('status')->unique()->values()->all());
        $this->assertTrue($task->fresh()->isCancelled());

        // peserta: hilang dari daftar, tidak bisa dibuka (detail, chat, riwayat)
        $rows = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]))->viewData('rows');
        $this->assertSame(['Tugas lain'], $rows->map(function ($r) { return $r['task']->title; })->all());
        $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->assertForbidden();
        $this->actingAs($f['b'])->get(route('user.work-logs.show', $task))->assertForbidden();
        $this->actingAs($f['a'])->getJson(route('work-logs.chat.index', $task))->assertForbidden();
        $this->actingAs($f['a'])->getJson(route('work-logs.history', $task))->assertForbidden();

        // mentor: tetap ada, bisa difilter "Batal"
        $res = $this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month]))->assertOk();
        $this->assertSame(2, $res->viewData('stats')['semua']);
        $this->assertSame(1, $res->viewData('stats')['batal']);
        $res->assertSee('Tugas dibatalkan', false);
        $only = $this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month, 'status' => 'batal']))->viewData('rows');
        $this->assertSame(['Tugas dibatalkan'], $only->map(function ($r) { return $r['task']->title; })->all());
        $show = $this->actingAs($f['mentor'])->get(route('work-logs.show', $task))->assertOk()->getContent();
        $this->assertStringContainsString('Tugas ini dibatalkan', $show);

        // riwayat: satu catatan (A dan B digabung) + catatan mentor
        $h = $this->historyText($task);
        $this->assertStringContainsString($f['mentor']->id . ':Status (Peserta A, Peserta B)=Belum dikerjakan>Batal', $h);
        $this->assertStringContainsString('Catatan mentor=>Proyek dihentikan', $h);

        // membatalkan lagi -> tidak ada perubahan
        $this->actingAs($f['mentor'])->post(route('work-logs.cancel', $task))->assertRedirect();
        $this->assertSame(1, WorkTaskMessage::where('work_task_id', $task->id)->where('kind', WorkTaskMessage::EDIT)->count());
    }

    public function test_mentor_status_change_to_batal_and_back_restores_for_intern(): void
    {
        $f = $this->fixture();
        $month = Carbon::today()->format('Y-m');
        $task = $this->mentorTask($f['mentor'], [$f['a'], $f['b']], Carbon::today()->toDateString());
        $rowA = $task->assignees()->where('user_id', $f['a']->id)->first();

        // hanya A dibatalkan -> A tidak melihat, B tetap melihat
        $this->actingAs($f['mentor'])->post(route('work-logs.status', [$task, $rowA]), ['status' => 'batal', 'review_note' => 'Mengundurkan diri'])->assertSessionHasNoErrors();
        $this->assertSame('batal', $rowA->fresh()->status);
        $this->assertCount(0, $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]))->viewData('rows'));
        $this->assertCount(1, $this->actingAs($f['b'])->get(route('user.work-logs.index', ['month' => $month]))->viewData('rows'));
        $this->assertFalse($task->fresh()->isCancelled(), 'belum seluruh peserta batal');

        // diaktifkan kembali oleh mentor -> muncul lagi di halaman A
        $this->actingAs($f['mentor'])->post(route('work-logs.status', [$task, $rowA]), ['status' => 'belum'])->assertSessionHasNoErrors();
        $this->assertSame('belum', $rowA->fresh()->status);
        $this->assertCount(1, $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]))->viewData('rows'));
        $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->assertOk();

        $h = $this->historyText($task);
        $this->assertStringContainsString('Status (Peserta A)=Belum dikerjakan>Batal', $h);
        $this->assertStringContainsString('Status (Peserta A)=Batal>Belum dikerjakan', $h);
    }

    public function test_every_status_change_is_written_to_history(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']]);
        $row = $task->assignees()->first();

        // peserta mengajukan lalu menarik
        $this->actingAs($f['a'])->post(route('user.work-logs.submit', $task))->assertSessionHasNoErrors();
        $this->actingAs($f['a'])->post(route('user.work-logs.withdraw', $task))->assertSessionHasNoErrors();
        // mentor menyetujui
        $this->actingAs($f['a'])->post(route('user.work-logs.submit', $task));
        $this->actingAs($f['mentor'])->post(route('work-logs.status', [$task, $row]), ['status' => 'sudah']);
        // mentor mengedit -> status kembali belum (tercatat)
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), ['title' => 'Judul baru', 'description' => 'Deskripsi', 'require_attachment' => 0, 'participants' => [$f['a']->id], 'deadline' => Carbon::today()->addDays(7)->toDateString()]);

        $h = $this->historyText($task);
        $this->assertStringContainsString($f['a']->id . ':Status=Belum dikerjakan>Menunggu persetujuan', $h, 'peserta mengajukan');
        $this->assertStringContainsString($f['a']->id . ':Status=Menunggu persetujuan>Belum dikerjakan', $h, 'peserta menarik');
        $this->assertStringContainsString($f['mentor']->id . ':Status=Menunggu persetujuan>Sudah selesai', $h, 'mentor menyetujui');
        $this->assertStringContainsString('Judul=Dokumentasi>Judul baru|Status=Sudah selesai>Belum dikerjakan', $h, 'edit mereset status');

        // tampil di endpoint riwayat (popup) dan tidak di chat
        $hist = $this->actingAs($f['mentor'])->getJson(route('work-logs.history', $task))->assertOk()->json('messages');
        $this->assertGreaterThanOrEqual(5, count($hist));
        $this->assertCount(0, $this->actingAs($f['mentor'])->getJson(route('work-logs.chat.index', $task))->json('messages'));
    }

    public function test_edit_does_not_revive_cancelled_participant(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a'], $f['b']]);
        $rowA = $task->assignees()->where('user_id', $f['a']->id)->first();
        $this->actingAs($f['mentor'])->post(route('work-logs.status', [$task, $rowA]), ['status' => 'batal']);
        $task->assignees()->where('user_id', $f['b']->id)->update(['status' => 'sudah']);

        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), ['title' => 'Revisi', 'description' => 'Deskripsi', 'require_attachment' => 0, 'participants' => [$f['a']->id, $f['b']->id], 'deadline' => Carbon::today()->addDays(7)->toDateString()])->assertSessionHasNoErrors();

        $this->assertSame('batal', $rowA->fresh()->status, 'yang batal tetap batal');
        $this->assertSame('belum', $task->assignees()->where('user_id', $f['b']->id)->first()->status, 'yang lain kembali belum');
    }

    public function test_intern_cancels_own_task_and_confirmation_is_required_in_ui(): void
    {
        $f = $this->fixture();
        $month = Carbon::today()->format('Y-m');
        $task = $this->selfTask($f['a'], [$f['b']]);

        $page = $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->assertOk()->getContent();
        $this->assertStringContainsString(route('user.work-logs.cancel', $task), $page);
        $this->assertStringContainsString('data-confirm-title="Batalkan tugas?"', $page);
        $this->assertStringContainsString('dan rekan kolaborasi', $page);

        // rekan kolaborasi juga boleh membatalkan; berlaku untuk seluruh pengerja
        $this->actingAs($f['b'])->post(route('user.work-logs.cancel', $task))->assertSessionHasNoErrors();
        $this->assertSame(['batal'], $task->assignees()->pluck('status')->unique()->values()->all());
        $this->assertCount(0, $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]))->viewData('rows'));
        $this->assertCount(0, $this->actingAs($f['b'])->get(route('user.work-logs.index', ['month' => $month]))->viewData('rows'));
        $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->assertForbidden();

        // mentor tetap melihatnya sebagai Batal dan bisa mengaktifkan kembali
        $res = $this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month]))->assertOk();
        $this->assertSame(1, $res->viewData('stats')['batal']);
        $rowA = $task->assignees()->where('user_id', $f['a']->id)->first();
        $this->actingAs($f['mentor'])->post(route('work-logs.status', [$task, $rowA]), ['status' => 'menunggu']);
        $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->assertOk();
        $this->actingAs($f['b'])->get(route('user.work-logs.show', $task))->assertForbidden();

        $this->assertStringContainsString('Status (Peserta A, Peserta B)=Menunggu persetujuan>Batal', $this->historyText($task));
    }

    public function test_intern_cannot_cancel_mentor_task_outsider_task_or_approved_task(): void
    {
        $f = $this->fixture();
        $mt = $this->mentorTask($f['mentor'], [$f['a']]);
        $this->assertFalse($mt->canBeCancelledBy($f['a']));
        $this->actingAs($f['a'])->post(route('user.work-logs.cancel', $mt))->assertForbidden();
        $this->assertStringNotContainsString(route('user.work-logs.cancel', $mt), $this->actingAs($f['a'])->get(route('user.work-logs.show', $mt))->getContent());

        $st = $this->selfTask($f['a']);
        $this->actingAs($f['c'])->post(route('user.work-logs.cancel', $st))->assertForbidden();   // bukan pengerja

        $st->assignees()->update(['status' => 'sudah']);                                         // sudah disetujui mentor
        $this->actingAs($f['a'])->post(route('user.work-logs.cancel', $st))->assertForbidden();
        $this->assertSame('sudah', $st->assignees()->first()->status);

        // peserta tidak bisa memakai rute mentor (dialihkan oleh middleware peran), status tidak berubah
        $this->actingAs($f['a'])->post(route('work-logs.cancel', $mt))->assertRedirect();
        $this->assertSame('belum', $mt->assignees()->first()->status);
    }

    public function test_partner_request_for_cancelled_task_cannot_be_accepted(): void
    {
        $f = $this->fixture();
        $task = $this->selfTask($f['a']);
        $req = \App\Models\WorkTaskPartnerRequest::create(['work_task_id' => $task->id, 'from_user_id' => $f['a']->id, 'to_user_id' => $f['b']->id, 'status' => 'pending']);

        $this->actingAs($f['a'])->post(route('user.work-logs.cancel', $task));
        $this->actingAs($f['b'])->post(route('user.work-logs.requests.respond', $req), ['action' => 'accept']);

        $this->assertFalse($task->assignees()->where('user_id', $f['b']->id)->exists(), 'rekan tidak ikut ditambahkan');
        $this->assertSame('pending', $req->fresh()->status);
    }

    /* ---------- referensi tugas dari detail (mentor sama seperti peserta) ---------- */

    public function test_mentor_can_add_and_remove_any_reference_from_detail(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']]);
        $internRef = WorkTaskReference::create(['work_task_id' => $task->id, 'type' => 'link', 'name' => 'Ref dari peserta', 'url' => 'https://example.test/a', 'uploaded_by' => $f['a']->id]);

        // tampilan: tombol Tambah referensi (menu Tautan / Unggah), modal tautan, dan menu hapus pada referensi peserta
        $page = $this->actingAs($f['mentor'])->get(route('work-logs.show', $task))->assertOk()->getContent();
        foreach (['Referensi Tugas', 'Tambah referensi', 'Unggah dari perangkat', 'id="wl-ref-modal"', 'data-wl-autosubmit', 'Ref dari peserta', route('work-logs.references.store', $task)] as $needle) {
            $this->assertStringContainsString($needle, $page, $needle);
        }
        $this->assertSame(1, substr_count($page, 'Opsi referensi'), 'mentor bisa menghapus referensi buatan peserta');

        // tambah tautan & file
        $this->actingAs($f['mentor'])->post(route('work-logs.references.store', $task), ['reference_link' => 'https://example.test/materi', 'reference_link_label' => 'Materi Mentor'])->assertSessionHasNoErrors();
        $this->actingAs($f['mentor'])->post(route('work-logs.references.store', $task), ['reference_files' => [UploadedFile::fake()->create('panduan.pdf', 50, 'application/pdf'), UploadedFile::fake()->image('skema.png')]])->assertSessionHasNoErrors();

        $mine = $task->references()->where('uploaded_by', $f['mentor']->id)->get();
        $this->assertCount(3, $mine);
        $file = $mine->firstWhere('name', 'panduan.pdf');
        Storage::disk('public')->assertExists($file->path);
        $this->assertSame('belum', $task->assignees()->first()->status, 'status penyelesaian tidak berubah');

        // validasi
        $this->actingAs($f['mentor'])->post(route('work-logs.references.store', $task), [])->assertSessionHasErrors('reference_files');
        $this->actingAs($f['mentor'])->post(route('work-logs.references.store', $task), ['reference_link' => 'bukan-url'])->assertSessionHasErrors('reference_link');
        $this->actingAs($f['mentor'])->post(route('work-logs.references.store', $task), ['reference_files' => [UploadedFile::fake()->create('x.exe', 10)]])->assertSessionHasErrors('reference_files.0');

        // menu hapus muncul pada SEMUA referensi (3 milik mentor + 1 milik peserta), tujuan rute mentor
        $page = $this->actingAs($f['mentor'])->get(route('work-logs.show', $task))->getContent();
        $this->assertSame(4, substr_count($page, 'Opsi referensi'));
        $this->assertStringContainsString(route('work-logs.references.destroy', [$task, $file]), $page);
        $this->assertStringContainsString(route('work-logs.references.destroy', [$task, $internRef]), $page);
        $this->assertStringContainsString('yang diunggah Peserta A?', $page, 'konfirmasi menyebut pengunggah bila bukan miliknya');
        $this->assertStringContainsString('data-confirm="Hapus referensi ini?"', $page, 'milik sendiri: konfirmasi singkat');

        // mentor menghapus miliknya dan milik peserta
        $this->actingAs($f['mentor'])->delete(route('work-logs.references.destroy', [$task, $file]))->assertSessionHasNoErrors();
        $this->assertNull(WorkTaskReference::find($file->id));
        Storage::disk('public')->assertMissing($file->path);
        $this->actingAs($f['mentor'])->delete(route('work-logs.references.destroy', [$task, $internRef]))->assertSessionHasNoErrors();
        $this->assertNull(WorkTaskReference::find($internRef->id));

        // referensi milik tugas lain tidak bisa dihapus lewat tugas ini
        $otherTask = $this->mentorTask($f['mentor'], [$f['a']]);
        $otherRef = WorkTaskReference::create(['work_task_id' => $otherTask->id, 'type' => 'link', 'name' => 'Lain', 'url' => 'https://example.test/l', 'uploaded_by' => $f['mentor']->id]);
        $this->actingAs($f['mentor'])->delete(route('work-logs.references.destroy', [$task, $otherRef]))->assertNotFound();
        $this->assertNotNull(WorkTaskReference::find($otherRef->id));

        // riwayat
        $notes = WorkTaskMessage::where('kind', WorkTaskMessage::EDIT)->where('user_id', $f['mentor']->id)->get()->pluck('changes')->flatten(1)->pluck('note')->filter()->implode(' | ');
        $this->assertStringContainsString('Menambah: Materi Mentor', $notes);
        $this->assertStringContainsString('Menghapus: panduan.pdf.', $notes, 'milik sendiri: tanpa keterangan pengunggah');
        $this->assertStringContainsString('Menghapus: Ref dari peserta (diunggah Peserta A).', $notes, 'milik peserta: pengunggah dicatat');

        // referensi mentor terlihat oleh peserta
        $this->assertStringContainsString('Materi Mentor', $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->getContent());
    }

    public function test_mentor_reference_permissions(): void
    {
        $f = $this->fixture();
        $otherDivision = Division::create(['name' => 'Keuangan']);
        $strangerMentor = $this->makeUser('mentor', 'Mentor Lain', $otherDivision->id);

        $mentorTask = $this->mentorTask($f['mentor'], [$f['a']]);
        $selfTask = $this->selfTask($f['a']);   // dicatat peserta divisi Teknologi

        // mentor lain (tidak berhak melihat tugas) ditolak
        $this->actingAs($strangerMentor)->post(route('work-logs.references.store', $mentorTask), ['reference_link' => 'https://example.test/x'])->assertForbidden();
        $this->actingAs($strangerMentor)->post(route('work-logs.references.store', $selfTask), ['reference_link' => 'https://example.test/x'])->assertForbidden();
        $this->assertSame(0, WorkTaskReference::count());

        // mentor lain juga tidak bisa menghapus referensi pada tugas yang bukan wewenangnya
        $internOwn = WorkTaskReference::create(['work_task_id' => $selfTask->id, 'type' => 'link', 'name' => 'Milik peserta', 'url' => 'https://example.test/p', 'uploaded_by' => $f['a']->id]);
        $this->actingAs($strangerMentor)->delete(route('work-logs.references.destroy', [$selfTask, $internOwn]))->assertForbidden();
        $this->assertNotNull(WorkTaskReference::find($internOwn->id));
        // mentor divisi yang sama boleh menghapus referensi peserta pada tugas yang dicatat peserta
        $this->actingAs($f['mentor'])->delete(route('work-logs.references.destroy', [$selfTask, $internOwn]))->assertSessionHasNoErrors();
        $this->assertNull(WorkTaskReference::find($internOwn->id));

        // mentor divisi yang sama boleh menambah pada tugas yang dicatat peserta
        $this->actingAs($f['mentor'])->post(route('work-logs.references.store', $selfTask), ['reference_link' => 'https://example.test/ok', 'reference_link_label' => 'Acuan'])->assertSessionHasNoErrors();
        $this->assertSame(1, $selfTask->references()->count());
        $this->assertStringContainsString('Acuan', $this->actingAs($f['a'])->get(route('user.work-logs.show', $selfTask))->getContent());

        // peserta tetap tidak bisa memakai rute mentor
        $this->actingAs($f['a'])->post(route('work-logs.references.store', $mentorTask), ['reference_link' => 'https://example.test/x'])->assertRedirect();
        $this->assertSame(0, $mentorTask->references()->count());
    }

    /* ---------- form edit tidak lagi mengelola referensi (dikelola dari detail) ---------- */

    public function test_edit_form_has_no_reference_section_but_create_form_does(): void
    {
        $f = $this->fixture();
        $mentorTask = $this->mentorTask($f['mentor'], [$f['a']]);
        $selfTask = $this->selfTask($f['a']);
        foreach ([$mentorTask, $selfTask] as $t) {
            WorkTaskReference::create(['work_task_id' => $t->id, 'type' => 'link', 'name' => 'Ref Lama', 'url' => 'https://example.test/lama', 'uploaded_by' => $f['mentor']->id]);
        }

        $gone = ['name="reference_files[]"', 'wl-link-modal', 'data-wl-attach', 'remove_references', 'Lampiran referensi tugas', 'Ref Lama'];

        foreach ([$mentorTask, $selfTask] as $t) {
            $page = $this->actingAs($f['mentor'])->get(route('work-logs.edit', $t))->assertOk()->getContent();
            foreach ($gone as $needle) {
                $this->assertStringNotContainsString($needle, $page, "edit #{$t->id} tidak boleh memuat: $needle");
            }
            $this->assertStringContainsString('langsung di halaman detail tugas', $page, 'ada petunjuk ke halaman detail');
        }

        // form buat tugas baru tetap punya bagian referensi (belum ada halaman detail)
        $create = $this->actingAs($f['mentor'])->get(route('work-logs.create'))->assertOk()->getContent();
        foreach (['name="reference_files[]"', 'wl-link-modal', 'data-wl-attach', 'Lampiran referensi tugas'] as $needle) {
            $this->assertStringContainsString($needle, $create, "form buat: $needle");
        }
        $this->assertStringNotContainsString('langsung di halaman detail tugas', $create);
    }

    public function test_editing_a_task_leaves_references_untouched_and_ignores_reference_fields(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a']]);
        $ref = WorkTaskReference::create(['work_task_id' => $task->id, 'type' => 'link', 'name' => 'Ref Lama', 'url' => 'https://example.test/lama', 'uploaded_by' => $f['a']->id]);
        $self = $this->selfTask($f['a']);
        $selfRef = WorkTaskReference::create(['work_task_id' => $self->id, 'type' => 'link', 'name' => 'Ref Peserta', 'url' => 'https://example.test/p', 'uploaded_by' => $f['a']->id]);

        // PUT dengan field referensi (mis. form lama / request manual) tidak boleh menambah / menghapus apa pun
        $payload = ['title' => 'Revisi', 'description' => 'Deskripsi', 'require_attachment' => 0, 'participants' => [$f['a']->id], 'deadline' => Carbon::today()->addDays(5)->toDateString(),
            'reference_files' => [UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf')], 'reference_links' => ['https://example.test/baru'], 'remove_references' => [$ref->id]];
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $task), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Revisi', $task->fresh()->title);
        $this->assertSame(['Ref Lama'], $task->references()->pluck('name')->all());

        $selfPayload = ['title' => 'Revisi mandiri', 'description' => 'Deskripsi', 'start_date' => Carbon::today()->subDay()->toDateString(), 'end_date' => Carbon::today()->toDateString(),
            'reference_links' => ['https://example.test/baru'], 'remove_references' => [$selfRef->id]];
        $this->actingAs($f['mentor'])->put(route('work-logs.update', $self), $selfPayload)->assertSessionHasNoErrors();
        $this->assertSame('Revisi mandiri', $self->fresh()->title);
        $this->assertSame(['Ref Peserta'], $self->references()->pluck('name')->all());

        // riwayat edit tidak memuat catatan "Lampiran referensi"
        $labels = WorkTaskMessage::where('kind', WorkTaskMessage::EDIT)->get()->pluck('changes')->flatten(1)->pluck('label')->unique()->all();
        $this->assertNotContains('Lampiran referensi', $labels);
    }

    /* ---------- penanda di pojok kanan atas kartu detail ---------- */

    /** Ambil [isi pojok kanan atas, isi daftar baris] dari halaman detail. */
    private function headFlagsAndKv(string $html): array
    {
        $this->assertSame(1, preg_match('#<div class="wl-head-flags">(.*?)</div>#s', $html, $head), 'pojok kanan atas ada');
        $this->assertSame(1, preg_match('#<dl class="wl-kv">(.*?)</dl>#s', $html, $kv), 'daftar baris ada');

        return [$head[1], $kv[1]];
    }

    public function test_flags_sit_in_top_right_for_intern_and_mentor(): void
    {
        $f = $this->fixture();

        // ---- peserta, tugas dicatat sendiri (menunggu persetujuan) ----
        $self = $this->selfTask($f['a']);
        [$head, $kv] = $this->headFlagsAndKv($this->actingAs($f['a'])->get(route('user.work-logs.show', $self))->getContent());
        $this->assertStringContainsString('Dicatat sendiri', $head);
        $this->assertStringContainsString('Menunggu persetujuan', $head);
        $this->assertLessThan(strpos($head, 'Menunggu persetujuan'), strpos($head, 'Dicatat sendiri'), 'urutan: dicatat sendiri, ..., status paling kanan');
        $this->assertStringNotContainsString('Dicatat sendiri', $kv);
        $this->assertStringContainsString('Saya', $kv);

        // ---- peserta, tugas mentor ber-tenggat: hitung mundur + status di pojok, bukan di baris ----
        $mt = $this->mentorTask($f['mentor'], [$f['a']]);
        [$head, $kv] = $this->headFlagsAndKv($this->actingAs($f['a'])->get(route('user.work-logs.show', $mt))->getContent());
        $this->assertStringContainsString('wl-countdown', $head);
        $this->assertStringContainsString('Belum dikerjakan', $head);
        $this->assertStringNotContainsString('Dicatat sendiri', $head);
        $this->assertStringNotContainsString('wl-countdown', $kv);
        $this->assertStringContainsString('(tenggat)', $kv, 'jam tenggat tetap di baris Tanggal selesai');

        // ---- peserta, lewat tenggat ----
        $mt->update(['deadline' => Carbon::yesterday()->toDateString(), 'end_date' => Carbon::yesterday()->toDateString(), 'start_date' => Carbon::today()->subDays(5)->toDateString()]);
        [$head, $kv] = $this->headFlagsAndKv($this->actingAs($f['a'])->get(route('user.work-logs.show', $mt))->getContent());
        $this->assertStringContainsString('Lewat tenggat', $head);
        $this->assertStringNotContainsString('Lewat tenggat', $kv);

        // ---- mentor, satu peserta: label status tunggal ----
        $one = $this->mentorTask($f['mentor'], [$f['a']]);
        [$head, $kv] = $this->headFlagsAndKv($this->actingAs($f['mentor'])->get(route('work-logs.show', $one))->getContent());
        $this->assertStringContainsString('wl-countdown', $head);
        $this->assertStringContainsString('Belum dikerjakan', $head);
        $this->assertStringNotContainsString('wl-countdown', $kv);

        // ---- mentor, tugas dicatat peserta ----
        [$head, $kv] = $this->headFlagsAndKv($this->actingAs($f['mentor'])->get(route('work-logs.show', $self))->getContent());
        $this->assertStringContainsString('Dicatat peserta', $head);
        $this->assertStringNotContainsString('Dicatat peserta', $kv);

        // ---- mentor, banyak peserta: ringkasan jumlah per status ----
        $many = $this->mentorTask($f['mentor'], [$f['a'], $f['b'], $f['c']]);
        $many->assignees()->where('user_id', $f['a']->id)->update(['status' => 'menunggu']);
        $many->assignees()->where('user_id', $f['b']->id)->update(['status' => 'sudah']);
        [$head] = $this->headFlagsAndKv($this->actingAs($f['mentor'])->get(route('work-logs.show', $many))->getContent());
        foreach (['1 menunggu persetujuan', '1 sudah selesai', '1 belum dikerjakan'] as $needle) {
            $this->assertStringContainsString($needle, $head);
        }
        $this->assertStringNotContainsString('batal', $head);

        // ---- mentor, tugas dibatalkan: tanpa hitung mundur / lewat tenggat ----
        $this->actingAs($f['mentor'])->post(route('work-logs.cancel', $many));
        [$head] = $this->headFlagsAndKv($this->actingAs($f['mentor'])->get(route('work-logs.show', $many))->getContent());
        $this->assertStringContainsString('3 batal', $head);
        $this->assertStringNotContainsString('wl-countdown', $head);
        $this->assertStringNotContainsString('Lewat tenggat', $head);
    }

    /* ---------- log pekerjaan peserta dikelompokkan per tanggal (seperti mentor) ---------- */

    public function test_intern_index_is_grouped_by_start_date_with_day_headings(): void
    {
        $f = $this->fixture();
        $today = Carbon::today();
        $month = $today->format('Y-m');

        // B & C punya tanggal selesai sama (hari ini); C dibuat belakangan (id lebih besar) tapi mulai lebih awal.
        $a = $this->selfTask($f['a']);                                   // mulai hari ini
        $a->update(['title' => 'A hari ini']);
        $b = $this->mentorTask($f['mentor'], [$f['a']], $today->toDateString());
        $b->update(['title' => 'B kemarin', 'start_date' => $today->copy()->subDay()->toDateString()]);
        $c = $this->selfTask($f['a']);
        $c->update(['title' => 'C tiga hari lalu', 'worked_on' => $today->copy()->subDays(3)->toDateString(), 'start_date' => $today->copy()->subDays(3)->toDateString()]);
        $d = $this->selfTask($f['a']);                                   // juga mulai hari ini -> satu kelompok dengan A
        $d->update(['title' => 'D hari ini']);

        $res = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]))->assertOk();
        $groups = $res->viewData('groups');

        // urutan kelompok: tanggal mulai terbaru di atas
        $this->assertSame([
            $today->toDateString(),
            $today->copy()->subDay()->toDateString(),
            $today->copy()->subDays(3)->toDateString(),
        ], $groups->keys()->all());
        // dalam satu kelompok: tugas terbaru (id terbesar) di atas
        $this->assertSame(['D hari ini', 'A hari ini'], $groups[$today->toDateString()]->map(function ($r) { return $r['task']->title; })->all());
        $this->assertSame(['B kemarin'], $groups[$today->copy()->subDay()->toDateString()]->map(function ($r) { return $r['task']->title; })->all());

        // tampilan: tiga judul tanggal dengan keterangan relatif, kartu berada di bawah judul masing-masing
        $html = $res->getContent();
        $this->assertSame(3, substr_count($html, 'class="wl-day-head"'));
        foreach (['Hari ini', 'Kemarin', '3 hari yang lalu'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $order = [];
        foreach (['Hari ini', 'D hari ini', 'A hari ini', 'Kemarin', 'B kemarin', '3 hari yang lalu', 'C tiga hari lalu'] as $needle) {
            $order[] = strpos($html, '>' . $needle . '<') !== false ? strpos($html, '>' . $needle . '<') : strpos($html, $needle);
        }
        $sorted = $order; sort($sorted);
        $this->assertSame($sorted, $order, 'judul tanggal dan kartu muncul berurutan dari atas ke bawah');

        // pencarian dan filter status tetap mempertahankan pengelompokan
        $q = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month, 'q' => 'kemarin']));
        $this->assertSame([$today->copy()->subDay()->toDateString()], $q->viewData('groups')->keys()->all());
        $this->assertSame(1, substr_count($q->getContent(), 'class="wl-day-head"'));

        $none = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month, 'status' => 'sudah']));
        $this->assertSame(0, substr_count($none->getContent(), 'class="wl-day-head"'));
        $none->assertSee('Tidak ada tugas yang cocok', false);

        // tugas yang dibatalkan tidak muncul, dan kelompok tanggalnya ikut hilang bila kosong
        $this->actingAs($f['a'])->post(route('user.work-logs.cancel', $c));
        $after = $this->actingAs($f['a'])->get(route('user.work-logs.index', ['month' => $month]));
        $this->assertCount(2, $after->viewData('groups'));
        $this->assertSame(2, substr_count($after->getContent(), 'class="wl-day-head"'));

        // halaman mentor tetap seperti semula
        $this->assertGreaterThanOrEqual(1, substr_count($this->actingAs($f['mentor'])->get(route('work-logs.index', ['month' => $month]))->getContent(), 'class="wl-day-head"'));
    }

    /* ---------- markup yang dipakai CSS tampilan HP ---------- */

    public function test_mobile_layout_hooks_exist_in_markup(): void
    {
        $f = $this->fixture();
        $task = $this->mentorTask($f['mentor'], [$f['a'], $f['b']]);

        // toolbar detail: dipakai grid 2 kolom di HP
        foreach ([[$f['a'], route('user.work-logs.show', $task)], [$f['mentor'], route('work-logs.show', $task)]] as [$u, $url]) {
            $html = $this->actingAs($u)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('class="wl-head wl-head--bar"', $html);
            $this->assertStringContainsString('class="wl-bar-actions"', $html);
            $this->assertStringContainsString('wl-head-flags', $html);
        }

        // baris tombol filter + strip kartu status
        foreach ([[$f['a'], route('user.work-logs.index')], [$f['mentor'], route('work-logs.index')]] as [$u, $url]) {
            $html = $this->actingAs($u)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('wl-f wl-f--actions', $html);
            $this->assertStringContainsString('class="wl-stats"', $html);
        }

        // form tanggal peserta (dua kolom di HP) dan pemilih rekan dengan nama lengkap di bawah kartu
        $create = $this->actingAs($f['a'])->get(route('user.work-logs.create'))->assertOk()->getContent();
        $this->assertStringContainsString('wl-form-grid wl-form-grid--dates', $create);
        $this->assertStringContainsString('class="pc-pick-name">Peserta B<', $create);
        $mform = $this->actingAs($f['mentor'])->get(route('work-logs.create'))->assertOk()->getContent();
        $this->assertStringContainsString('class="pc-pick-name">Peserta A<', $mform);

        // panel diskusi: tombol tutup, kolom keterangan lampiran, dan penguncian scroll tersedia
        $show = $this->actingAs($f['a'])->get(route('user.work-logs.show', $task))->getContent();
        foreach (['id="wcClose"', 'id="wcCaption"', 'id="wcCompose"'] as $needle) {
            $this->assertStringContainsString($needle, $show);
        }
        $this->assertStringContainsString('wc-lock', file_get_contents(public_path('js/work-chat.js')));
        $this->assertStringContainsString('wc-lock', file_get_contents(public_path('css/work-chat.css')));
    }
}
