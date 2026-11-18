<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyWorkCompanyMasterTest extends TestCase
{
    use DatabaseTransactions;

    private const REQUIRED_NAMES = [
        'GDE (All)', 'GDE (Only)', 'KODIPA', 'JOTRE (All)', 'JOTRE (Only)', 'PJSE', 'ADP', 'SOKKO',
        'BISMO', 'WCP', 'TDP (BUJP)', 'PJSE (Utility)', 'Rentokil (Pest Control)', 'PMC',
        'Bhumi Phala (Maintenance EWS)', 'Environesia',
    ];

    protected User $admin;
    protected User $inventory;
    protected User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('admin.company.test@geodipa.co.id', User::ROLE_ADMIN_MANAGEMENT);
        $this->inventory = $this->makeUser('inventory.company.test@geodipa.co.id', User::ROLE_ADMIN_INVENTORY);
        $this->regular = $this->makeUser('user.company.test@geodipa.co.id', User::ROLE_USER);

        DailyWorkCompany::ensureRequired();
        $this->actingAs($this->admin);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Tester ' . $role, 'password' => bcrypt('password123'), 'role' => $role, 'status' => 'aktif']
        );
        $user->update(['role' => $role, 'status' => 'aktif']);

        return $user;
    }

    private function company(string $name): DailyWorkCompany
    {
        return DailyWorkCompany::where('name', $name)->firstOrFail();
    }

    private function workPayload(string $unit, array $override = []): array
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        return array_merge([
            'name' => 'Pekerjaan Uji ' . $unit,
            'unit' => $unit,
            'sika' => 'Cold Work',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Area Uji',
        ], $override);
    }

    public function test_all_sixteen_required_companies_exist_exactly_once_with_exact_spelling(): void
    {
        foreach (self::REQUIRED_NAMES as $name) {
            $this->assertSame(
                1,
                DailyWorkCompany::whereRaw('BINARY name = ?', [$name])->count(),
                "Perusahaan wajib '{$name}' harus ada tepat satu kali."
            );
        }

        $this->assertSame(16, DailyWorkCompany::where('is_required', true)->count());
    }

    public function test_company_relations_are_structured(): void
    {
        $this->assertSame('GDE (All)', $this->company('KODIPA')->parent->name);
        $this->assertSame('subcon', $this->company('KODIPA')->category);
        $this->assertSame('GDE (All)', $this->company('GDE (Only)')->parent->name);
        $this->assertSame('subcon', $this->company('GDE (Only)')->category);

        foreach (['JOTRE (Only)', 'PJSE', 'ADP', 'SOKKO', 'BISMO', 'WCP'] as $subcon) {
            $c = $this->company($subcon);
            $this->assertSame('subcon', $c->category, $subcon);
            $this->assertSame('JOTRE (All)', $c->parent->name, $subcon);
        }

        foreach (['GDE (All)', 'JOTRE (All)'] as $main) {
            $c = $this->company($main);
            $this->assertSame('main', $c->category, $main);
            $this->assertNull($c->parent_company_id, $main);
        }

        foreach (['TDP (BUJP)', 'PJSE (Utility)', 'Rentokil (Pest Control)', 'PMC', 'Bhumi Phala (Maintenance EWS)', 'Environesia'] as $ind) {
            $c = $this->company($ind);
            $this->assertSame('independent', $c->category, $ind);
            $this->assertNull($c->parent_company_id, $ind);
        }

        $this->assertNotSame($this->company('PJSE')->id, $this->company('PJSE (Utility)')->id);
        $this->assertNotSame($this->company('PJSE')->category, $this->company('PJSE (Utility)')->category);
    }

    public function test_sync_required_is_idempotent_and_does_not_override_user_status(): void
    {
        $before = DailyWorkCompany::count();

        $rentokil = $this->company('Rentokil (Pest Control)');
        $rentokil->update(['is_active' => false]);

        DailyWorkCompany::syncRequired();
        DailyWorkCompany::syncRequired();

        $this->assertSame($before, DailyWorkCompany::count());
        $this->assertFalse($this->company('Rentokil (Pest Control)')->is_active);
        $this->assertSame($rentokil->id, $this->company('Rentokil (Pest Control)')->id);
    }

    public function test_daily_work_dropdown_uses_master_data_and_lists_active_companies_once(): void
    {
        $res = $this->get(route('daily-work.index'));
        $res->assertStatus(200);

        $names = collect($res->viewData('companyGroups'))
            ->flatMap(fn ($g) => collect($g['items'])->pluck('name'));

        foreach (self::REQUIRED_NAMES as $name) {
            $this->assertSame(1, $names->filter(fn ($n) => $n === $name)->count(), "{$name} harus tampil tepat satu kali");
        }
        $this->assertSame($names->count(), $names->unique()->count());

        // Perusahaan baru yang aktif langsung muncul di dropdown tanpa ubah kode
        $this->postJson(route('daily-work.masters.companies.store'), [
            'name' => 'Vendor Pemenang Tender Baru',
            'category' => 'independent',
            'is_active' => true,
        ])->assertOk()->assertJsonPath('success', true);

        $this->get(route('daily-work.index'))->assertSee('Vendor Pemenang Tender Baru');
    }

    public function test_admin_can_add_subcon_and_independent_company_with_validation(): void
    {
        $gde = $this->company('GDE (All)');

        $this->postJson(route('daily-work.masters.companies.store'), [
            'name' => 'Subcon Baru GDE',
            'has_parent' => true,
            'parent_company_id' => $gde->id,
            'is_active' => true,
        ])->assertOk();
        $this->assertSame($gde->id, $this->company('Subcon Baru GDE')->parent_company_id);

        // Independen: opsi has_parent = false -> induk dikosongkan
        $this->postJson(route('daily-work.masters.companies.store'), [
            'name' => 'Vendor Mandiri Baru',
            'has_parent' => false,
            'parent_company_id' => $gde->id,
        ])->assertOk();
        $this->assertNull($this->company('Vendor Mandiri Baru')->parent_company_id);

        // Subcon tanpa induk ditolak
        $this->postJson(route('daily-work.masters.companies.store'), [
            'name' => 'Subcon Tanpa Induk',
            'has_parent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('parent_company_id');

        // Subcon tidak boleh dipilih sebagai induk
        $this->postJson(route('daily-work.masters.companies.store'), [
            'name' => 'Subcon Induk Salah',
            'has_parent' => true,
            'parent_company_id' => $this->company('KODIPA')->id,
        ])->assertStatus(422)->assertJsonValidationErrors('parent_company_id');

        // Induk tidak aktif ditolak
        $inactiveParent = $this->company('JOTRE (All)');
        $inactiveParent->update(['is_active' => false]);
        $this->postJson(route('daily-work.masters.companies.store'), [
            'name' => 'Subcon Induk Nonaktif',
            'has_parent' => true,
            'parent_company_id' => $inactiveParent->id,
        ])->assertStatus(422)->assertJsonValidationErrors('parent_company_id');
        $inactiveParent->update(['is_active' => true]);

        // Nama wajib & tidak boleh duplikat (tanpa memperhatikan huruf besar/kecil dan spasi ganda)
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => '', 'has_parent' => false])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'pjse', 'has_parent' => false])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => '  PJSE   (Utility) ', 'category' => 'independent'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_edit_keeps_id_and_updates_history_by_id_only(): void
    {
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'Vendor Lama Uji', 'category' => 'independent'])->assertOk();
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'Vendor Lama Uji 2', 'category' => 'independent'])->assertOk();
        $a = $this->company('Vendor Lama Uji');
        $b = $this->company('Vendor Lama Uji 2');

        $workA = DailyWork::create($this->workPayload('Vendor Lama Uji'));
        $workB = DailyWork::create($this->workPayload('Vendor Lama Uji 2'));
        $this->assertSame($a->id, $workA->company_id);
        $this->assertSame($b->id, $workB->company_id);

        $this->putJson(route('daily-work.masters.companies.update', $a->id), [
            'name' => 'Vendor Baru Uji',
            'category' => 'independent',
            'is_active' => true,
        ])->assertOk();

        $this->assertSame($a->id, $this->company('Vendor Baru Uji')->id);
        $this->assertSame('Vendor Baru Uji', $workA->fresh()->unit);
        $this->assertSame($a->id, $workA->fresh()->company_id);
        // Histori perusahaan lain dengan nama mirip tidak tergabung
        $this->assertSame('Vendor Lama Uji 2', $workB->fresh()->unit);
        $this->assertSame($b->id, $workB->fresh()->company_id);
    }

    public function test_required_company_name_is_locked_but_other_fields_are_editable(): void
    {
        $kodipa = $this->company('KODIPA');

        $this->putJson(route('daily-work.masters.companies.update', $kodipa->id), [
            'name' => 'KODIPA Baru',
            'category' => 'subcon',
            'parent_company_id' => $this->company('GDE (All)')->id,
        ])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->putJson(route('daily-work.masters.companies.update', $kodipa->id), [
            'name' => 'KODIPA',
            'has_parent' => true,
            'parent_company_id' => $this->company('JOTRE (All)')->id,
        ])->assertOk();
        $this->assertSame('JOTRE (All)', $this->company('KODIPA')->parent->name);
    }

    public function test_parent_company_cannot_lose_main_category_while_having_subcons(): void
    {
        $this->putJson(route('daily-work.masters.companies.update', $this->company('JOTRE (All)')->id), [
            'name' => 'JOTRE (All)',
            'category' => 'independent',
        ])->assertStatus(422)->assertJsonValidationErrors('category');
    }

    public function test_deactivate_hides_from_new_work_but_keeps_history_and_can_be_reactivated(): void
    {
        $rentokil = $this->company('Rentokil (Pest Control)');
        $work = DailyWork::create($this->workPayload('Rentokil (Pest Control)'));

        $this->postJson(route('daily-work.masters.companies.toggle', $rentokil->id), ['is_active' => false])
            ->assertOk()->assertJsonPath('is_active', false);

        // Tidak tersedia di dropdown pekerjaan baru
        $names = collect($this->get(route('daily-work.index'))->viewData('companyGroups'))
            ->flatMap(fn ($g) => collect($g['items'])->pluck('name'));
        $this->assertFalse($names->contains('Rentokil (Pest Control)'));

        // Tidak bisa dipakai untuk pekerjaan baru
        $this->postJson(route('daily-work.store'), $this->workPayload('Rentokil (Pest Control)'))
            ->assertStatus(422)->assertJsonValidationErrors('unit');

        // Histori tetap benar (detail, dan edit tanpa mengganti perusahaan tetap diizinkan)
        $this->assertSame('Rentokil (Pest Control)', $work->fresh()->unit);
        $this->getJson(route('daily-work.show', $work->id))->assertJsonPath('work.unit', 'Rentokil (Pest Control)');
        $this->putJson(route('daily-work.update', $work->id), $this->workPayload('Rentokil (Pest Control)', ['location' => 'Lokasi Baru']))
            ->assertOk()->assertJsonPath('work.unit', 'Rentokil (Pest Control)');

        // Tetap ada di filter / master (tidak dihapus)
        $this->assertTrue(DailyWorkCompany::where('name', 'Rentokil (Pest Control)')->exists());

        // Aktifkan kembali
        $this->postJson(route('daily-work.masters.companies.toggle', $rentokil->id), ['is_active' => true])->assertOk();
        $this->postJson(route('daily-work.store'), $this->workPayload('Rentokil (Pest Control)'))->assertStatus(201);
    }

    public function test_subcons_and_main_choices_are_stored_as_themselves(): void
    {
        foreach (['KODIPA', 'PJSE', 'ADP', 'SOKKO', 'BISMO', 'WCP', 'GDE (Only)', 'JOTRE (Only)', 'PJSE (Utility)', 'TDP (BUJP)'] as $unit) {
            $res = $this->postJson(route('daily-work.store'), $this->workPayload($unit))->assertStatus(201);
            $this->assertSame($unit, $res->json('work.unit'), "Pilihan {$unit} tidak boleh berubah menjadi perusahaan lain.");
            $this->assertSame($this->company($unit)->id, $res->json('work.company_id'));
        }

        // Parent company (GDE (All), JOTRE (All)) cannot be used as executor
        foreach (['GDE (All)', 'JOTRE (All)'] as $parentUnit) {
            $this->postJson(route('daily-work.store'), $this->workPayload($parentUnit))
                ->assertStatus(422)
                ->assertJsonValidationErrors('unit');
        }
    }

    public function test_cannot_create_work_with_unknown_company(): void
    {
        $this->postJson(route('daily-work.store'), $this->workPayload('Perusahaan Tidak Ada'))
            ->assertStatus(422)->assertJsonValidationErrors('unit');
    }

    public function test_required_or_used_company_cannot_be_deleted(): void
    {
        $this->deleteJson(route('daily-work.masters.companies.destroy', $this->company('PMC')->id))
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'Vendor Terpakai', 'category' => 'independent'])->assertOk();
        DailyWork::create($this->workPayload('Vendor Terpakai'));
        $this->deleteJson(route('daily-work.masters.companies.destroy', $this->company('Vendor Terpakai')->id))
            ->assertStatus(422);

        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'Vendor Salah Ketik', 'category' => 'independent'])->assertOk();
        $this->deleteJson(route('daily-work.masters.companies.destroy', $this->company('Vendor Salah Ketik')->id))
            ->assertOk();
        $this->assertFalse(DailyWorkCompany::where('name', 'Vendor Salah Ketik')->exists());
    }

    public function test_only_admin_management_can_mutate_company_master(): void
    {
        $target = $this->company('PMC');

        // Regular user: mutasi ditolak (403), tetapi membaca master tetap diperbolehkan (200)
        $this->actingAs($this->regular);
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'Tidak Boleh', 'has_parent' => false])->assertStatus(403);
        $this->putJson(route('daily-work.masters.companies.update', $target->id), ['name' => 'PMC', 'has_parent' => false])->assertStatus(403);
        $this->postJson(route('daily-work.masters.companies.toggle', $target->id), ['is_active' => false])->assertStatus(403);
        $this->deleteJson(route('daily-work.masters.companies.destroy', $target->id))->assertStatus(403);
        $this->getJson(route('daily-work.masters.index'))->assertOk()->assertJsonPath('success', true);

        // Admin inventory: diblokir dari seluruh akses modul Daily Work (403)
        $this->actingAs($this->inventory);
        $this->postJson(route('daily-work.masters.companies.store'), ['name' => 'Tidak Boleh 2', 'has_parent' => false])->assertStatus(403);
        $this->getJson(route('daily-work.masters.index'))->assertStatus(403);

        $this->assertFalse(DailyWorkCompany::where('name', 'Tidak Boleh')->exists());
        $this->assertTrue($this->company('PMC')->is_active);

        // Tanpa login
        auth()->logout();
        $this->postJson(route('daily-work.masters.companies.toggle', $target->id))->assertStatus(401);
    }

    public function test_master_json_exposes_table_columns(): void
    {
        $res = $this->getJson(route('daily-work.masters.index'))->assertOk();
        $kodipa = collect($res->json('companies'))->firstWhere('name', 'KODIPA');

        $this->assertSame('Subcon', $kodipa['category_label']);
        $this->assertSame('GDE (All)', $kodipa['parent_name']);
        $this->assertTrue($kodipa['is_active']);
    }
}

