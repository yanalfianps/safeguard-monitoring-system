<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\Sikka;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyWorkSikaFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminManagement;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminManagement = User::firstOrCreate(
            ['email' => 'admin.dw.test@geodipa.co.id'],
            [
                'name' => 'Admin Management Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminManagement->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user.dw.test@geodipa.co.id'],
            [
                'name' => 'User Tester',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_USER,
                'status' => 'aktif',
            ]
        );
        $this->regularUser->update(['role' => User::ROLE_USER, 'status' => 'aktif']);

        $this->actingAs($this->adminManagement);
    }

    /**
     * Test 1: Cold Work & Hot Work tidak muncul kembali setelah dihapus,
     * bahkan setelah reload halaman, API masters/options, atau pemanggilan ensureDefaults().
     */
    public function test_cold_and_hot_work_do_not_reappear_on_page_load_or_ensure_defaults(): void
    {
        // Pastikan Cold Work dan Hot Work tidak ada di database
        $this->assertDatabaseMissing('sikkas', ['name' => 'Cold Work']);
        $this->assertDatabaseMissing('sikkas', ['name' => 'Hot Work']);

        // Panggil ensureDefaults() secara langsung
        Sikka::ensureDefaults();

        $this->assertDatabaseMissing('sikkas', ['name' => 'Cold Work']);
        $this->assertDatabaseMissing('sikkas', ['name' => 'Hot Work']);

        // Akses halaman index Daily Work
        $resIndex = $this->get(route('daily-work.index'));
        $resIndex->assertStatus(200);

        // Akses endpoint masters options
        $resOptions = $this->getJson(route('daily-work.masters.index'));
        $resOptions->assertStatus(200);

        $sikaNames = collect($resOptions->json('sikkas'))->pluck('name')->all();
        $this->assertNotContains('Cold Work', $sikaNames);
        $this->assertNotContains('Hot Work', $sikaNames);

        // Pastikan opsi SIKA lainnya tetap ada
        $expected = ['Ruang Terbatas', 'Ketinggian', 'Penggalian', 'Listrik', 'Isolasi Energi', 'Lifting', 'Panas'];
        foreach ($expected as $exp) {
            $this->assertContains($exp, $sikaNames);
        }
    }

    /**
     * Test 2: SIKA bersifat opsional - pekerjaan dapat disimpan tanpa memilih SIKA sama sekali (0 pilihan).
     */
    public function test_can_create_daily_work_without_sika(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $response = $this->postJson(route('daily-work.store'), [
            'name' => 'Pekerjaan Tanpa SIKA',
            'unit' => 'GDE (Only)',
            'sika' => [],
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Gedung Administrasi',
            'description' => 'Meeting koordinasi internal non-teknis.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $workId = $response->json('work.id');
        $work = DailyWork::findOrFail($workId);

        $this->assertNull($work->sikka);
        $this->assertNull($work->sika);
        $this->assertEquals([], $work->sika_array);
    }

    /**
     * Test 3: Pekerjaan dapat disimpan dengan 1 jenis SIKA.
     */
    public function test_can_create_daily_work_with_single_sika(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $response = $this->postJson(route('daily-work.store'), [
            'name' => 'Pekerjaan Ketinggian Tunggal',
            'unit' => 'KODIPA',
            'sika' => ['Ketinggian'],
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Menara Pendingin',
        ]);

        $response->assertStatus(201);
        $workId = $response->json('work.id');
        $work = DailyWork::findOrFail($workId);

        $this->assertEquals('Ketinggian', $work->sika);
        $this->assertEquals(['Ketinggian'], $work->sika_array);
    }

    /**
     * Test 4: Pekerjaan dapat disimpan dengan banyak SIKA (multiple checkboxes).
     */
    public function test_can_create_daily_work_with_multiple_sikas(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $selected = ['Ruang Terbatas', 'Listrik', 'Panas'];

        $response = $this->postJson(route('daily-work.store'), [
            'name' => 'Pekerjaan Multipel SIKA',
            'unit' => 'PJSE',
            'sika' => $selected,
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Turbine Hall',
        ]);

        $response->assertStatus(201);
        $workId = $response->json('work.id');
        $work = DailyWork::findOrFail($workId);

        $this->assertEquals('Ruang Terbatas, Listrik, Panas', $work->sika);
        $this->assertEquals($selected, $work->sika_array);

        // Periksa API show / getWorks
        $showRes = $this->get(route('daily-work.show', $workId));
        $showRes->assertStatus(200);
        $showRes->assertSee('Ruang Terbatas');
        $showRes->assertSee('Listrik');
        $showRes->assertSee('Panas');
    }

    /**
     * Test 5: Update pekerjaan untuk mengubah, menambah, atau menghapus pilihan SIKA.
     */
    public function test_can_update_daily_work_sika_selection(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $work = DailyWork::create([
            'name' => 'Pekerjaan Awal',
            'unit' => 'ADP',
            'sika' => 'Listrik',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Gardu Induk',
        ]);

        // Ubah menjadi multi-sika
        $updateRes = $this->putJson(route('daily-work.update', $work->id), [
            'name' => 'Pekerjaan Awal Diperbarui',
            'unit' => 'ADP',
            'sika' => ['Listrik', 'Isolasi Energi'],
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Gardu Induk',
        ]);

        $updateRes->assertStatus(200);
        $this->assertEquals('Listrik, Isolasi Energi', $work->fresh()->sika);

        // Hapus semua pilihan SIKA (menjadi kosong / opsional)
        $clearRes = $this->putJson(route('daily-work.update', $work->id), [
            'name' => 'Pekerjaan Selesai Tanpa SIKA',
            'unit' => 'ADP',
            'sika' => [],
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Gardu Induk',
        ]);

        $clearRes->assertStatus(200);
        $this->assertNull($work->fresh()->sika);
    }

    /**
     * Test 6: Toggle status aktif/nonaktif SIKA master.
     * SIKA yang dinonaktifkan tidak boleh muncul pada opsi aktif pekerjaan baru, tapi riwayat lama tetap aman.
     */
    public function test_toggle_sika_status_and_inactivity_handling(): void
    {
        $sika = Sikka::where('name', 'Lifting')->firstOrFail();
        $this->assertTrue((bool) $sika->is_active);

        // Toggle menjadi nonaktif
        $toggleRes = $this->postJson(route('daily-work.masters.sika.toggle', $sika->id));
        $toggleRes->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse((bool) $sika->fresh()->is_active);

        // Cek endpoint masters options: sika_active tidak boleh menyertakan Lifting
        $optionsRes = $this->getJson(route('daily-work.masters.index'));
        $activeSikaNames = collect($optionsRes->json('sikkas'))
            ->where('is_active', true)
            ->pluck('name')
            ->all();

        $this->assertNotContains('Lifting', $activeSikaNames);

        // Pekerjaan lama yang memakai 'Lifting' tetap aman dan terbaca
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $oldWork = DailyWork::create([
            'name' => 'Pekerjaan Menggunakan Lifting Lama',
            'unit' => 'SOKKO',
            'sika' => 'Lifting',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Yard',
        ]);

        $this->assertEquals('Lifting', $oldWork->fresh()->sika);

        // Toggle kembali menjadi aktif
        $this->postJson(route('daily-work.masters.sika.toggle', $sika->id))
            ->assertStatus(200);
        $this->assertTrue((bool) $sika->fresh()->is_active);
    }

    /**
     * Test 7: SIKA yang sedang digunakan oleh pekerjaan tidak boleh dihapus.
     */
    public function test_cannot_delete_sika_in_use(): void
    {
        $today = Carbon::now('Asia/Jakarta')->toDateString();

        $sika = Sikka::where('name', 'Ruang Terbatas')->firstOrFail();

        // Buat pekerjaan yang memakai Ruang Terbatas (bisa sendiri atau bersama yang lain)
        DailyWork::create([
            'name' => 'Pekerjaan Tangki',
            'unit' => 'BISMO',
            'sika' => 'Ruang Terbatas, Listrik',
            'start_date' => $today,
            'end_date' => $today,
            'location' => 'Tangki 3',
        ]);

        // Coba hapus Ruang Terbatas
        $deleteRes = $this->deleteJson(route('daily-work.masters.sika.destroy', $sika->id));
        $deleteRes->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sikkas', ['id' => $sika->id]);
    }
}
