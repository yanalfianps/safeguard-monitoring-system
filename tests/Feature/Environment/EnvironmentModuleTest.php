<?php

namespace Tests\Feature\Environment;

use App\Models\EnvironmentMaster;
use App\Models\EnvironmentLb3Record;
use App\Models\EnvironmentNonLb3Record;
use App\Models\EnvironmentRklRplRecord;
use App\Models\EnvironmentPpichRecord;
use App\Models\EnvironmentEnergyRecord;
use App\Models\EnvironmentEwsRecord;
use App\Models\EnvironmentInspectionRecord;
use App\Models\EnvironmentWeatherRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnvironmentModuleTest extends TestCase
{
    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Clean any existing environment test records
        EnvironmentLb3Record::query()->delete();
        EnvironmentNonLb3Record::query()->delete();
        EnvironmentRklRplRecord::query()->delete();
        EnvironmentPpichRecord::query()->delete();
        EnvironmentEnergyRecord::query()->delete();
        EnvironmentEwsRecord::query()->delete();
        EnvironmentWeatherRecord::query()->delete();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test_env@geodipa.co.id'],
            [
                'name' => 'Admin Environment Test',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'active',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_test_env@geodipa.co.id'],
            [
                'name' => 'Regular User Environment Test',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_USER,
                'status' => 'active',
            ]
        );
    }

    /**
     * 1. Sidebar includes Environment and 6 main submenus (PPKH included, Master Data integrated into Ringkasan)
     */
    public function test_sidebar_includes_environment_menu_and_submenus(): void
    {
        $response = $this->actingAs($this->admin)->get(route('environment.index'));

        $response->assertStatus(200);
        $response->assertSee('Environment');
        $response->assertSee('LB3');
        $response->assertSee('Non-LB3');
        $response->assertSee('RKL–RPL');
        $response->assertSee('Inspeksi');
        $response->assertSee('Energi');
        $response->assertSee('EWS &amp; Cuaca', false);

        // Master Data accessible via button in Ringkasan page
        $response->assertSee('Master Data');
        $response->assertSee(route('environment.master.index'));
    }

    /**
     * 2. LB3 Lifecycle (Create, Multi-file Upload, Edit, Append, Delete)
     */
    public function test_lb3_lifecycle_with_multi_upload_and_characteristics(): void
    {
        $unit = EnvironmentMaster::where('category', 'unit')->where('is_active', true)->first();

        $file1 = UploadedFile::fake()->create('dokumen1.pdf', 500, 'application/pdf');
        $file2 = UploadedFile::fake()->image('foto1.png');

        // Create
        $response = $this->actingAs($this->admin)->post(route('environment.lb3.store'), [
            'waste_name' => 'Oli Bekas Genset',
            'amount' => 125.5,
            'unit_id' => $unit->id,
            'entry_date' => '2026-10-25',
            'characteristics' => ['Mudah menyala', 'Beracun'],
            'description' => 'Disimpan di TPS B3 Utama',
            'attachments' => [$file1, $file2],
        ]);

        $response->assertRedirect(route('environment.lb3.index'));
        $record = EnvironmentLb3Record::where('waste_name', 'Oli Bekas Genset')->first();
        $this->assertNotNull($record);
        $this->assertEquals(125.5, $record->amount);
        $this->assertCount(2, $record->attachment_list);
        $this->assertContains('Mudah menyala', $record->characteristics_list);

        // Edit and append new file from another folder
        $file3 = UploadedFile::fake()->create('dokumen_pendukung_folder2.pdf', 300, 'application/pdf');
        $oldFile = $record->attachment_list[0];

        $updateResponse = $this->actingAs($this->admin)->put(route('environment.lb3.update', $record->id), [
            'waste_name' => 'Oli Bekas Genset Terisi',
            'amount' => 150.0,
            'unit_id' => $unit->id,
            'entry_date' => '2026-10-26',
            'characteristics' => ['Mudah menyala', 'Beracun', 'Reaktif'],
            'description' => 'Diperbarui',
            'attachments' => [$file3],
        ]);

        $updateResponse->assertRedirect(route('environment.lb3.index'));
        $record->refresh();
        $this->assertEquals('Oli Bekas Genset Terisi', $record->waste_name);
        $this->assertCount(3, $record->attachment_list);
        $this->assertContains($oldFile, $record->attachment_list);

        // Explicit deletion of 1 file
        $fileToDelete = $record->attachment_list[0];
        $deleteFileResponse = $this->actingAs($this->admin)->put(route('environment.lb3.update', $record->id), [
            'waste_name' => 'Oli Bekas Genset Terisi',
            'amount' => 150.0,
            'unit_id' => $unit->id,
            'entry_date' => '2026-10-26',
            'deleted_attachments' => [$fileToDelete],
        ]);

        $deleteFileResponse->assertRedirect(route('environment.lb3.index'));
        $record->refresh();
        $this->assertCount(2, $record->attachment_list);
        $this->assertNotContains($fileToDelete, $record->attachment_list);

        // Delete record
        $delResponse = $this->actingAs($this->admin)->delete(route('environment.lb3.destroy', $record->id));
        $delResponse->assertRedirect(route('environment.lb3.index'));
        $this->assertNull(EnvironmentLb3Record::find($record->id));
    }

    /**
     * 3. Non-LB3 Lifecycle: Free text source, optional transport_date, Edit, Delete
     */
    public function test_non_lb3_lifecycle_with_free_text_source(): void
    {
        $unit = EnvironmentMaster::where('category', 'unit')->where('is_active', true)->first();

        // 1. Create with free text source (no source_id required)
        $response = $this->actingAs($this->admin)->post(route('environment.non-lb3.store'), [
            'waste_name' => 'Sampah Kertas Kantor & Dapur',
            'source' => 'Gedung Administrasi Lantai 2',
            'amount' => 45.0,
            'unit_id' => $unit->id,
            'entry_date' => '2026-10-20',
            'transport_date' => null,
            'description' => 'Dari ruangan staf finance',
        ]);

        $response->assertRedirect(route('environment.non-lb3.index'));
        $record = EnvironmentNonLb3Record::where('waste_name', 'Sampah Kertas Kantor & Dapur')->first();
        $this->assertNotNull($record);
        $this->assertEquals('Gedung Administrasi Lantai 2', $record->source);
        $this->assertNull($record->transport_date);

        // 2. Update when transported
        $updateResponse = $this->actingAs($this->admin)->put(route('environment.non-lb3.update', $record->id), [
            'waste_name' => 'Sampah Kertas Kantor & Dapur',
            'source' => 'Gedung Administrasi Utama',
            'amount' => 50.0,
            'unit_id' => $unit->id,
            'entry_date' => '2026-10-20',
            'transport_date' => '2026-10-25',
            'description' => 'Sudah diangkut oleh rekanan daur ulang',
        ]);

        $updateResponse->assertRedirect(route('environment.non-lb3.index'));
        $record->refresh();
        $this->assertEquals('Gedung Administrasi Utama', $record->source);
        $this->assertNotNull($record->transport_date);
        $this->assertEquals('2026-10-25', $record->transport_date->format('Y-m-d'));
    }

    /**
     * 4. RKL-RPL Lifecycle: Unit only from active dropdown or null (no free text required)
     */
    public function test_rkl_rpl_lifecycle_with_dropdown_unit(): void
    {
        $param = EnvironmentMaster::where('category', 'rkl_rpl_parameter')->where('is_active', true)->first();
        $loc = EnvironmentMaster::where('category', 'rkl_rpl_location')->where('is_active', true)->first();
        $unit = EnvironmentMaster::where('category', 'unit')->where('is_active', true)->first();

        // 1. Create with unit_id from dropdown
        $response = $this->actingAs($this->admin)->post(route('environment.rkl-rpl.store'), [
            'parameter_id' => $param->id,
            'location_id' => $loc->id,
            'sampling_date' => '2026-10-24',
            'measurement_value' => '52.4',
            'unit_id' => $unit->id,
            'description' => 'Pengukuran kebisingan siang hari',
        ]);

        $response->assertRedirect(route('environment.rkl-rpl.index'));
        $record = EnvironmentRklRplRecord::where('measurement_value', '52.4')->first();
        $this->assertNotNull($record);
        $this->assertEquals($param->name, $record->parameter_label);
        $this->assertEquals($loc->name, $record->location_label);
        $this->assertEquals($unit->name, $record->unit_label);

        // 2. Create with empty unit (if not relevant)
        $responseNoUnit = $this->actingAs($this->admin)->post(route('environment.rkl-rpl.store'), [
            'parameter_id' => $param->id,
            'location_id' => $loc->id,
            'sampling_date' => '2026-10-25',
            'measurement_value' => 'Nihil / Memenuhi',
            'unit_id' => null,
            'description' => 'Kondisi visual normal tanpa satuan',
        ]);

        $responseNoUnit->assertRedirect(route('environment.rkl-rpl.index'));
        $recordNoUnit = EnvironmentRklRplRecord::where('measurement_value', 'Nihil / Memenuhi')->first();
        $this->assertNotNull($recordNoUnit);
        $this->assertNull($recordNoUnit->unit_id);
    }

    /**
     * 5. PPKH Lifecycle: 3-Field Structure (monitoring_lokasi SK 2013/2024, activity, attachments)
     */
    public function test_ppkh_simplified_lifecycle(): void
    {
        $file1 = UploadedFile::fake()->create('sk_izin_ppkh.pdf', 300, 'application/pdf');

        // 1. Create PPKH with 3 fields
        $response = $this->actingAs($this->admin)->post(route('environment.ppkh.store'), [
            'monitoring_lokasi' => 'SK 2013',
            'activity' => 'Patroli pengawasan patok batas kawasan hutan lindung Blok A',
            'attachments' => [$file1],
        ]);

        $response->assertRedirect(route('environment.ppkh.index'));
        $record = EnvironmentPpichRecord::where('location_name', 'SK 2013')->first();
        $this->assertNotNull($record);
        $this->assertEquals('SK 2013', $record->monitoring_lokasi);
        $this->assertStringContainsString('Patroli pengawasan patok batas', $record->activity_text);
        $this->assertCount(1, $record->attachment_list);

        // 2. Edit PPKH (change to SK 2024, update activity, add another file)
        $file2 = UploadedFile::fake()->image('foto_patok.jpg');
        $updateResponse = $this->actingAs($this->admin)->put(route('environment.ppkh.update', $record->id), [
            'monitoring_lokasi' => 'SK 2024',
            'activity' => 'Verifikasi koordinat patok batas kawasan SK 2024 terkoordinasi BPKH',
            'attachments' => [$file2],
        ]);

        $updateResponse->assertRedirect(route('environment.ppkh.index'));
        $record->refresh();
        $this->assertEquals('SK 2024', $record->monitoring_lokasi);
        $this->assertStringContainsString('Verifikasi koordinat', $record->activity_text);
        $this->assertCount(2, $record->attachment_list);

        // 3. Delete PPKH
        $delResponse = $this->actingAs($this->admin)->delete(route('environment.ppkh.destroy', $record->id));
        $delResponse->assertRedirect(route('environment.ppkh.index'));
        $this->assertNull(EnvironmentPpichRecord::find($record->id));
    }

    /**
     * 6. Energi Lifecycle (BBM & Listrik, Duplicate Prevention)
     */
    public function test_energy_lifecycle_and_duplicate_prevention(): void
    {
        // 1. Create BBM record
        $response = $this->actingAs($this->admin)->post(route('environment.energi.store'), [
            'energy_type' => 'bbm',
            'month' => 5,
            'year' => 2026,
            'usage_amount' => 1500.5,
            'description' => 'Pemakaian solar genset & armada',
        ]);

        $response->assertRedirect();
        $record = EnvironmentEnergyRecord::where('energy_type', 'bbm')
            ->where('month', 5)
            ->where('year', 2026)
            ->first();
        $this->assertNotNull($record);
        $this->assertEquals(1500.5, $record->usage_amount);
        $this->assertEquals('Liter', $record->unit);

        // 2. Duplicate prevention for same energy_type + month + year
        $dupResponse = $this->actingAs($this->admin)->post(route('environment.energi.store'), [
            'energy_type' => 'bbm',
            'month' => 5,
            'year' => 2026,
            'usage_amount' => 1200.0,
        ]);
        $dupResponse->assertSessionHasErrors(['month']);

        // 3. Different energy_type (Listrik) with same month + year is allowed
        $listrikResponse = $this->actingAs($this->admin)->post(route('environment.energi.store'), [
            'energy_type' => 'listrik',
            'month' => 5,
            'year' => 2026,
            'usage_amount' => 45000.0,
            'description' => 'Tagihan PLN Gardu Utama',
        ]);
        $listrikResponse->assertRedirect();
        $listrikRecord = EnvironmentEnergyRecord::where('energy_type', 'listrik')
            ->where('month', 5)
            ->where('year', 2026)
            ->first();
        $this->assertNotNull($listrikRecord);
        $this->assertEquals('kWh', $listrikRecord->unit);
    }

    /**
     * 7. EWS Lifecycle (Location, Free Text Condition, Auth User Recording)
     */
    public function test_ews_lifecycle(): void
    {
        $loc = EnvironmentMaster::where('category', 'ews_location')->where('is_active', true)->first();

        $response = $this->actingAs($this->admin)->post(route('environment.ews.store'), [
            'location_id' => $loc->id,
            'recording_date' => '2026-10-27',
            'condition' => 'Sensor gas H2S berbunyi intermittant, dilakukan inspeksi teknis dan kalibrasi ulang.',
            'description' => 'Kondisi kembali normal pukul 14:00',
        ]);

        $response->assertRedirect(route('environment.ews.index'));
        $record = EnvironmentEwsRecord::where('location_id', $loc->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($this->admin->id, $record->recorded_by);
        $this->assertStringContainsString('Sensor gas H2S', $record->condition);
    }

    /**
     * 8. Master Data Management: Create, Edit, Toggle, and Relational Delete Guard
     */
    public function test_master_data_management_and_historical_integrity(): void
    {
        // 1. Create new custom unit
        $response = $this->actingAs($this->admin)->post(route('environment.master.store'), [
            'category' => 'unit',
            'name' => 'M3 (Meter Kubik)',
            'code' => 'M3',
            'order_index' => 10,
        ]);

        $response->assertRedirect();
        $newMaster = EnvironmentMaster::where('name', 'M3 (Meter Kubik)')->first();
        $this->assertNotNull($newMaster);
        $this->assertTrue($newMaster->is_active);

        // 2. Toggle active state
        $this->actingAs($this->admin)->post(route('environment.master.toggle', $newMaster->id));
        $newMaster->refresh();
        $this->assertFalse($newMaster->is_active);

        // Deactivated master should NOT be in active scope
        $activeUnits = EnvironmentMaster::active()->byCategory('unit')->pluck('id')->toArray();
        $this->assertNotContains($newMaster->id, $activeUnits);

        // 3. Historical usage protects against hard deletion
        $usedUnit = EnvironmentMaster::where('category', 'unit')->first();
        EnvironmentLb3Record::create([
            'waste_name' => 'Limbah Uji Guard',
            'amount' => 10,
            'unit_id' => $usedUnit->id,
            'unit_name' => $usedUnit->name,
            'entry_date' => '2026-10-01',
        ]);

        // Attempting to delete $usedUnit must be blocked
        $delResponse = $this->actingAs($this->admin)->delete(route('environment.master.destroy', $usedUnit->id));
        $delResponse->assertSessionHas('error');
        $this->assertNotNull(EnvironmentMaster::find($usedUnit->id));

        // Unused custom master can be deleted safely
        $delCustom = $this->actingAs($this->admin)->delete(route('environment.master.destroy', $newMaster->id));
        $delCustom->assertRedirect();
        $this->assertNull(EnvironmentMaster::find($newMaster->id));
    }

    /**
     * 9. Role Permissions: Regular user can view but cannot write
     */
    public function test_role_permissions_for_environment(): void
    {
        // Regular user can access index views
        $this->actingAs($this->regularUser)->get(route('environment.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.lb3.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.non-lb3.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.rkl-rpl.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.ppkh.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.ppich.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.energi.index'))->assertStatus(200);
        $this->actingAs($this->regularUser)->get(route('environment.ews.index'))->assertStatus(200);

        // Regular user CANNOT create LB3 (blocked by 'admin' middleware)
        $response = $this->actingAs($this->regularUser)->post(route('environment.lb3.store'), [
            'waste_name' => 'Unauthorized Waste',
            'amount' => 10,
            'unit_id' => 1,
            'entry_date' => '2026-10-01',
        ]);
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));

        // Regular user CANNOT access Master Data Management
        $masterResponse = $this->actingAs($this->regularUser)->get(route('environment.master.index'));
        $this->assertTrue(in_array($masterResponse->getStatusCode(), [403, 302]));
    }

    /**
     * 10. Export Endpoints (PDF and Excel/CSV) return 200
     */
    public function test_export_endpoints_render_successfully(): void
    {
        // Overview Ringkasan PDF
        $this->actingAs($this->admin)->get(route('environment.export-pdf', ['year' => 2026]))->assertStatus(200);

        // Submenu exports with and without filters
        $this->actingAs($this->admin)->get(route('environment.lb3.export-pdf', ['search' => 'oli']))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.lb3.export-excel'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('environment.non-lb3.export-pdf', ['source' => 'Office']))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.non-lb3.export-excel'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('environment.rkl-rpl.export-pdf', ['parameter' => 1]))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.rkl-rpl.export-excel'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('environment.ppkh.export-pdf', ['monitoring_lokasi' => 'SK 2013']))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.ppkh.export-excel'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('environment.ppich.export-pdf'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.ppich.export-excel'))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('environment.energi.export-pdf', ['tab' => 'bbm', 'year' => 2026]))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.energi.export-excel', ['tab' => 'bbm', 'year' => 2026]))->assertStatus(200);

        $this->actingAs($this->admin)->get(route('environment.ews.export-pdf', ['location' => 1]))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('environment.ews.export-excel'))->assertStatus(200);
    }

    /**
     * 11. Master Data Active Categories & Navigation Integration
     */
    public function test_master_data_active_categories_and_navigation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('environment.master.index'));

        $response->assertStatus(200);
        $response->assertSee('Kembali ke Ringkasan');
        $response->assertSee(route('environment.index'));

        // Active categories must be present
        $response->assertSee('Satuan');
        $response->assertSee('Karakteristik LB3');
        $response->assertSee('Parameter RKL–RPL');
        $response->assertSee('Lokasi Pemantauan RKL–RPL');
        $response->assertSee('Lokasi EWS');

        // Irrelevant categories must NOT be in active filter tabs
        $response->assertDontSee('Lokasi PPICH');
        $response->assertDontSee('Aktivitas PPICH');
        $response->assertDontSee('Acuan SK PPICH');
        $response->assertDontSee('Sumber Limbah Non-LB3');

        // Attempting to create in legacy ppich category must be rejected by validation
        $storeResponse = $this->actingAs($this->admin)->post(route('environment.master.store'), [
            'category' => 'ppich_location',
            'name' => 'Legacy Disallowed Master',
        ]);
        $storeResponse->assertSessionHasErrors(['category']);
    }

    /**
     * 12. Unit mapping per submenu ensures relevant units appear only in their designated submenu
     */
    public function test_unit_mapping_per_submenu(): void
    {
        // 1. In LB3, view receives mapped units (Ton, Kilogram, Liter, Drum, Sak/karung) and not RKL-RPL units
        $lb3Response = $this->actingAs($this->admin)->get(route('environment.lb3.index'));
        $lb3Response->assertStatus(200);
        $lb3Units = $lb3Response->viewData('units')->pluck('name')->toArray();

        $this->assertContains('Ton', $lb3Units);
        $this->assertContains('Kilogram (kg)', $lb3Units);
        $this->assertContains('Liter', $lb3Units);
        $this->assertNotContains('dBA', $lb3Units);
        $this->assertNotContains('mg/L', $lb3Units);
        $this->assertNotContains('kWh', $lb3Units);

        // 2. In Non-LB3, view receives mapped units
        $nonLb3Response = $this->actingAs($this->admin)->get(route('environment.non-lb3.index'));
        $nonLb3Response->assertStatus(200);
        $nonLb3Units = $nonLb3Response->viewData('units')->pluck('name')->toArray();

        $this->assertContains('Ton', $nonLb3Units);
        $this->assertContains('Kilogram (kg)', $nonLb3Units);
        $this->assertNotContains('dBA', $nonLb3Units);

        // 3. In RKL-RPL, view receives mapped units (dBA, mg/L, ppm, mg/Nm3, °C, %, pH)
        $rklResponse = $this->actingAs($this->admin)->get(route('environment.rkl-rpl.index'));
        $rklResponse->assertStatus(200);
        $rklUnits = $rklResponse->viewData('units')->pluck('name')->toArray();

        $this->assertContains('dBA', $rklUnits);
        $this->assertContains('mg/L', $rklUnits);
        $this->assertNotContains('Ton', $rklUnits);
        $this->assertNotContains('Drum', $rklUnits);
    }

    /**
     * 13. Master data unit submodules mapping save and update
     */
    public function test_master_data_submodule_mapping_save_and_update(): void
    {
        // Create new unit with specific submodules
        $response = $this->actingAs($this->admin)->post(route('environment.master.store'), [
            'category' => 'unit',
            'name' => 'Karton Box',
            'code' => 'BOX',
            'submodules' => ['lb3', 'non_lb3'],
        ]);

        $response->assertRedirect();
        $boxUnit = EnvironmentMaster::where('name', 'Karton Box')->first();
        $this->assertNotNull($boxUnit);
        $this->assertEquals(['lb3', 'non_lb3'], $boxUnit->submodules);

        // Update submodules mapping
        $updateResp = $this->actingAs($this->admin)->put(route('environment.master.update', $boxUnit->id), [
            'name' => 'Karton Box Besar',
            'code' => 'BOX-L',
            'submodules' => ['non_lb3'],
        ]);

        $updateResp->assertRedirect();
        $boxUnit->refresh();
        $this->assertEquals('Karton Box Besar', $boxUnit->name);
        $this->assertEquals(['non_lb3'], $boxUnit->submodules);

        // Clean up
        $boxUnit->delete();
    }

    /**
     * 14. PPKH monitoring lokasi accepts SK 2013 and SK 2024 and rejects other values
     */
    public function test_ppkh_monitoring_lokasi_sk2013_and_sk2024(): void
    {
        // 1. Can save SK 2013
        $resp1 = $this->actingAs($this->admin)->post(route('environment.ppkh.store'), [
            'monitoring_lokasi' => 'SK 2013',
            'activity' => 'Patroli pengawasan patok batas area SK 2013',
        ]);
        $resp1->assertRedirect(route('environment.ppkh.index'));
        $this->assertDatabaseHas('environment_ppich_records', [
            'location_name' => 'SK 2013',
            'activity_name' => 'Patroli pengawasan patok batas area SK 2013',
        ]);

        // 2. Can save SK 2024
        $resp2 = $this->actingAs($this->admin)->post(route('environment.ppkh.store'), [
            'monitoring_lokasi' => 'SK 2024',
            'activity' => 'Pemantauan tata batas perluasan SK 2024',
        ]);
        $resp2->assertRedirect(route('environment.ppkh.index'));
        $this->assertDatabaseHas('environment_ppich_records', [
            'location_name' => 'SK 2024',
        ]);

        // 3. Disallowed value is rejected by validation
        $invalidResp = $this->actingAs($this->admin)->post(route('environment.ppkh.store'), [
            'monitoring_lokasi' => 'SK 1999',
            'activity' => 'Invalid activity',
        ]);
        $invalidResp->assertSessionHasErrors(['monitoring_lokasi']);
    }

    /**
     * 15. Only Export PDF button is displayed, and no Export Excel button exists in Environment UI
     */
    public function test_only_export_pdf_displayed_and_no_export_excel_button_in_environment_views(): void
    {
        $routes = [
            route('environment.index'),
            route('environment.lb3.index'),
            route('environment.non-lb3.index'),
            route('environment.rkl-rpl.index'),
            route('environment.ppkh.index'),
            route('environment.inspeksi.index'),
            route('environment.energi.index'),
            route('environment.ews.index'),
            route('environment.cuaca.index'),
        ];

        foreach ($routes as $url) {
            $resp = $this->actingAs($this->admin)->get($url);
            $resp->assertStatus(200);

            // Export PDF must be present
            $resp->assertSee('Export PDF');
            $resp->assertSee('bi-file-earmark-pdf');

            // Export Excel must NOT be present
            $resp->assertDontSee('Ekspor Excel');
            $resp->assertDontSee('Export Excel');
            $resp->assertDontSee('bi-file-earmark-excel');
        }
    }

    /**
     * 16. Inspeksi Lifecycle: Dropdown jenis, dropdown lokasi, free text kondisi, multi-upload lampiran
     */
    public function test_inspeksi_lifecycle_and_view(): void
    {
        $file1 = UploadedFile::fake()->create('laporan_inspeksi.pdf', 300, 'application/pdf');

        // 1. Create Inspeksi with free text condition and attachments
        $response = $this->actingAs($this->admin)->post(route('environment.inspeksi.store'), [
            'inspection_type' => 'Spill Kit',
            'location_name' => 'Area Well Pad',
            'condition' => 'Kondisi spill kit lengkap, absorbent pad dan boom tersusun rapi tanpa kontaminasi.',
            'inspection_date' => now()->toDateString(),
            'attachments' => [$file1],
        ]);

        $response->assertRedirect(route('environment.inspeksi.index'));
        $record = EnvironmentInspectionRecord::where('inspection_type', 'Spill Kit')->first();
        $this->assertNotNull($record);
        $this->assertEquals('Area Well Pad', $record->location_name);
        $this->assertStringContainsString('absorbent pad', $record->condition);
        $this->assertCount(1, $record->attachment_list);

        // 2. View Index renders properly
        $viewResp = $this->actingAs($this->admin)->get(route('environment.inspeksi.index'));
        $viewResp->assertStatus(200);
        $viewResp->assertSee('Spill Kit');
        $viewResp->assertSee('Area Well Pad');
        $viewResp->assertSee('Inspeksi Lingkungan');

        // 3. Edit Inspeksi
        $updateResp = $this->actingAs($this->admin)->put(route('environment.inspeksi.update', $record->id), [
            'inspection_type' => 'TPS LB3',
            'location_name' => 'Temporary Office',
            'condition' => 'Kondisi TPS LB3 bersih, simbol label bahaya terpasang jelas.',
        ]);

        $updateResp->assertRedirect(route('environment.inspeksi.index'));
        $record->refresh();
        $this->assertEquals('TPS LB3', $record->inspection_type);
        $this->assertEquals('Temporary Office', $record->location_name);

        // 4. Export PDF Inspeksi
        $pdfResp = $this->actingAs($this->admin)->get(route('environment.inspeksi.export-pdf'));
        $pdfResp->assertStatus(200);

        // 5. Delete Inspeksi
        $delResp = $this->actingAs($this->admin)->delete(route('environment.inspeksi.destroy', $record->id));
        $delResp->assertRedirect(route('environment.inspeksi.index'));
        $this->assertNull(EnvironmentInspectionRecord::find($record->id));
    }

    /**
     * 17. Cuaca Lifecycle: Automatic Indonesian day calculation (Rabu, Kamis, Jumat), numerical parameters with units, no location requirement
     */
    public function test_cuaca_lifecycle_with_numerical_parameters_and_units(): void
    {
        // 1. Create weather record on 2026-11-04 without location (office measurement point)
        $response = $this->actingAs($this->admin)->post(route('environment.cuaca.store'), [
            'recording_date' => '2026-11-04',
            'recording_time' => '09:30',
            'wind_speed' => 4.25,
            'rainfall' => 12.80,
            'temperature' => 24.6,
            'humidity' => 76.5,
            'notes' => 'Arah angin dominan ke timur, berawan tipis.',
        ]);

        $response->assertRedirect(route('environment.cuaca.index'));
        $record = EnvironmentWeatherRecord::first();
        $this->assertNotNull($record);
        $this->assertEquals('2026-11-04', $record->recording_date->format('Y-m-d'));
        // 04/11/2026 must be Rabu
        $this->assertEquals('Rabu', $record->day_name);
        $this->assertEquals(4.25, $record->wind_speed);
        $this->assertEquals(12.80, $record->rainfall);
        $this->assertEquals(24.6, $record->temperature);
        $this->assertEquals(76.5, $record->humidity);
        $this->assertEquals('4,25 m/s', $record->wind_speed_label);
        $this->assertEquals('12,80 mm/hr', $record->rainfall_label);
        $this->assertEquals('24,6 °C', $record->temperature_label);
        $this->assertEquals('76,5 %', $record->humidity_label);

        // 2. View page renders with explicit units and automatic day
        $viewResp = $this->actingAs($this->admin)->get(route('environment.cuaca.index'));
        $viewResp->assertStatus(200);
        $viewResp->assertSee('Rabu');
        $viewResp->assertSee('m/s');
        $viewResp->assertSee('mm/hr');
        $viewResp->assertSee('°C');
        $viewResp->assertSee('%');
        $viewResp->assertSee('EWS &amp; Cuaca', false);
        $viewResp->assertSee('Peringatan Dini (EWS)'); // Tab switcher link

        // 3. Edit Weather Record to 2026-11-05 (must automatically become Kamis)
        $updateResp = $this->actingAs($this->admin)->put(route('environment.cuaca.update', $record->id), [
            'recording_date' => '2026-11-05',
            'recording_time' => '11:00',
            'wind_speed' => 5.10,
            'rainfall' => 0.00,
            'temperature' => 28.2,
            'humidity' => 65.0,
            'notes' => 'Cerah tanpa hujan.',
        ]);

        $updateResp->assertRedirect(route('environment.cuaca.index'));
        $record->refresh();
        $this->assertEquals('2026-11-05', $record->recording_date->format('Y-m-d'));
        $this->assertEquals('Kamis', $record->day_name);
        $this->assertEquals(5.10, $record->wind_speed);

        // 4. Edit Weather Record to 2026-11-06 (must automatically become Jumat)
        $updateResp2 = $this->actingAs($this->admin)->put(route('environment.cuaca.update', $record->id), [
            'recording_date' => '2026-11-06',
            'recording_time' => '14:00',
            'wind_speed' => 3.50,
            'rainfall' => 5.20,
            'temperature' => 26.0,
            'humidity' => 80.0,
        ]);
        $updateResp2->assertRedirect(route('environment.cuaca.index'));
        $record->refresh();
        $this->assertEquals('2026-11-06', $record->recording_date->format('Y-m-d'));
        $this->assertEquals('Jumat', $record->day_name);

        // 5. Export PDF Cuaca
        $pdfResp = $this->actingAs($this->admin)->get(route('environment.cuaca.export-pdf'));
        $pdfResp->assertStatus(200);

        // 6. Delete Weather Record
        $delResp = $this->actingAs($this->admin)->delete(route('environment.cuaca.destroy', $record->id));
        $delResp->assertRedirect(route('environment.cuaca.index'));
        $this->assertNull(EnvironmentWeatherRecord::find($record->id));
    }

    /**
     * 18. Environment Overview displays updated Inspeksi and Cuaca summaries
     */
    public function test_environment_overview_includes_inspeksi_and_cuaca(): void
    {
        // Seed an inspection record and a weather record
        EnvironmentInspectionRecord::create([
            'inspection_type' => 'Spill Kit',
            'location_name' => 'Area Well Pad',
            'condition' => 'Kotak spill kit dalam kondisi prima.',
            'inspection_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        EnvironmentWeatherRecord::create([
            'recording_date' => now()->toDateString(),
            'recording_time' => '08:00',
            'location_name' => 'Area PP',
            'wind_speed' => 3.2,
            'rainfall' => 0.5,
            'temperature' => 22.5,
            'humidity' => 82.0,
            'notes' => 'Pagi hari berkabut tipis.',
            'recorded_by' => $this->admin->id,
        ]);

        $resp = $this->actingAs($this->admin)->get(route('environment.index'));
        $resp->assertStatus(200);
        $resp->assertSee('Ringkasan Inspeksi Lingkungan');
        $resp->assertSee('Monitoring Parameter Cuaca');
        $resp->assertSee('Spill Kit');
        $resp->assertSee('m/s');
        $resp->assertSee('mm/hr');
        $resp->assertSee('°C');
        $resp->assertSee('Early Warning System (EWS)'); // EWS summary still present
    }

    /**
     * 19. Master Data includes inspection_type, inspection_location, and weather_parameter categories
     */
    public function test_master_data_includes_inspeksi_and_weather_categories(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('environment.master.index'));
        $resp->assertStatus(200);
        $resp->assertSee('Jenis Inspeksi');
        $resp->assertSee('Lokasi Inspeksi');
        $resp->assertSee('Parameter Cuaca');

        // Check unit submodule mapping includes Cuaca
        $unitOptions = EnvironmentMaster::unitSubmoduleOptions();
        $this->assertArrayHasKey('cuaca', $unitOptions);
        $this->assertEquals('Cuaca', $unitOptions['cuaca']);
    }
}
