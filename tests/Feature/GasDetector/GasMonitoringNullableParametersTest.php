<?php

namespace Tests\Feature\GasDetector;

use App\Models\GasMonitoring;
use App\Models\User;
use App\Models\WellPad;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GasMonitoringNullableParametersTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.gas.test@geodipa.co.id'],
            [
                'name' => 'Admin Gas Test',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'Aktif']);

        WellPad::ensureDefaultsSeeded();
    }

    /**
     * Uji 1: Menyimpan batch measurement dengan parameter yang dikosongkan (null).
     * Memastikan kolom h2s_level, co_level, o2_level, lel_level, dan pressure
     * bernilai NULL (bukan 0) di database tanpa integrity constraint violation.
     */
    public function test_can_store_batch_measurements_with_nullable_parameters(): void
    {
        $wp7 = WellPad::where('name', 'Well Pad 7')->first();
        $today = Carbon::today()->toDateString();

        $payload = [
            'measurement_date' => $today,
            'measurements' => [
                [
                    'well_pad_id' => $wp7->id,
                    'well_pad_name' => 'Well Pad 7',
                    'co_level' => null,
                    'o2_level' => null,
                    'h2s_level' => null, // h2s_level sengaja null
                    'lel_level' => null,
                    'pressure' => 15.2, // hanya ukur pressure
                    'notes' => 'Hanya pengukuran pressure sumur',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('gas-detector.measurements.store-batch'), $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $record = GasMonitoring::where('well_pad', 'Well Pad 7')
            ->whereDate('measurement_date', $today)
            ->latest('id')
            ->first();

        $this->assertNotNull($record);
        $this->assertNull($record->co_level);
        $this->assertNull($record->o2_level);
        $this->assertNull($record->h2s_level);
        $this->assertNull($record->lel_level);
        $this->assertEquals(15.2, $record->pressure);

        // Status per parameter kosong tidak dianggap otomatis Sesuai (aman)
        $this->assertEquals('-', $record->co_status);
        $this->assertEquals('-', $record->o2_status);
        $this->assertEquals('-', $record->h2s_status);
        $this->assertEquals('-', $record->lel_status);
        $this->assertEquals('Sesuai', $record->pressure_status);
        // Karena tidak ada gas yang diukur, overall status tidak otomatis Sesuai
        $this->assertEquals('-', $record->overall_status);
    }

    /**
     * Uji 2: Pengukuran tunggal (storeMeasurement) tidak menggunakan default 0 untuk parameter kosong.
     */
    public function test_single_measurement_does_not_default_to_zero_for_empty_parameters(): void
    {
        $today = Carbon::today()->toDateString();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('gas-detector.measurements.store'), [
                'measurement_date' => $today,
                'well_pad' => 'Well Pad 9',
                'co_level' => 10.5,
                'o2_level' => null,
                'h2s_level' => null,
                'lel_level' => null,
                'pressure' => null,
                'notes' => 'Pengukuran CO saja',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $record = GasMonitoring::where('well_pad', 'Well Pad 9')
            ->whereDate('measurement_date', $today)
            ->latest('id')
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(10.5, $record->co_level);
        $this->assertNull($record->o2_level);
        $this->assertNull($record->h2s_level);
        $this->assertNull($record->lel_level);
        $this->assertNull($record->pressure);

        $this->assertEquals('Sesuai', $record->co_status);
        $this->assertEquals('-', $record->o2_status);
        $this->assertEquals('-', $record->h2s_status);
        $this->assertEquals('-', $record->lel_status);
        $this->assertEquals('-', $record->pressure_status);
        // Gas yang diukur aman (CO 10.5 <= 25), sehingga overall_status Sesuai
        $this->assertEquals('Sesuai', $record->overall_status);
    }

    /**
     * Uji 3: Validasi numerik: jika parameter diisi bukan angka atau negatif, validasi harus gagal.
     */
    public function test_validation_rejects_invalid_numeric_values(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Nilai string non-angka
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('gas-detector.measurements.store'), [
                'measurement_date' => $today,
                'well_pad' => 'Well Pad 9',
                'co_level' => 'bukan-angka',
                'h2s_level' => 5.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['co_level']);

        // 2. Nilai negatif
        $negResponse = $this->actingAs($this->adminUser)
            ->postJson(route('gas-detector.measurements.store'), [
                'measurement_date' => $today,
                'well_pad' => 'Well Pad 9',
                'h2s_level' => -1.5,
            ]);

        $negResponse->assertStatus(422)
            ->assertJsonValidationErrors(['h2s_level']);

        // 3. O2 di atas 100%
        $o2Response = $this->actingAs($this->adminUser)
            ->postJson(route('gas-detector.measurements.store'), [
                'measurement_date' => $today,
                'well_pad' => 'Well Pad 9',
                'o2_level' => 150,
            ]);

        $o2Response->assertStatus(422)
            ->assertJsonValidationErrors(['o2_level']);
    }

    /**
     * Uji 4: Status keselamatan tidak menganggap O2 kosong sebagai 0 (yang mana 0 < 19.5 akan keliru memicu Tidak Sesuai).
     * Jika parameter lain melebihi NAB, status overall tetap Tidak Sesuai.
     */
    public function test_safety_calculation_does_not_treat_null_o2_as_zero_and_correctly_detects_exceeded(): void
    {
        $today = Carbon::today()->toDateString();

        // Kasus A: H2S melebihi NAB (15 > 10), O2 kosong (null).
        // H2S harus Tidak Sesuai, overall status harus Tidak Sesuai. O2 status harus '-' (bukan Tidak Sesuai karena nol).
        $rec = GasMonitoring::create([
            'measurement_date' => $today,
            'well_pad' => 'Well Pad 28',
            'co_level' => 5.0,
            'o2_level' => null,
            'h2s_level' => 15.0, // > 10 ppm
            'lel_level' => null,
            'pressure' => null,
        ]);

        $this->assertEquals('Sesuai', $rec->co_status);
        $this->assertEquals('-', $rec->o2_status);
        $this->assertEquals('Tidak Sesuai', $rec->h2s_status);
        $this->assertEquals('-', $rec->lel_status);
        $this->assertEquals('Tidak Sesuai', $rec->overall_status);

        // Kasus B: CO melebihi NAB (30 > 25), parameter lain null.
        $recCo = GasMonitoring::create([
            'measurement_date' => $today,
            'well_pad' => 'Well Pad 30',
            'co_level' => 30.0,
            'o2_level' => null,
            'h2s_level' => null,
            'lel_level' => null,
            'pressure' => null,
        ]);

        $this->assertEquals('Tidak Sesuai', $recCo->co_status);
        $this->assertEquals('Tidak Sesuai', $recCo->overall_status);
    }

    /**
     * Uji 5: Update pengukuran memungkinkan pengosongan parameter menjadi NULL (clearing value).
     */
    public function test_can_update_measurement_to_null_values(): void
    {
        $today = Carbon::today()->toDateString();

        $rec = GasMonitoring::create([
            'measurement_date' => $today,
            'well_pad' => 'Well Pad 31',
            'co_level' => 10.0,
            'o2_level' => 20.9,
            'h2s_level' => 3.5,
            'lel_level' => 1.0,
            'pressure' => 14.0,
        ]);

        $this->assertEquals(10.0, $rec->co_level);
        $this->assertEquals('Sesuai', $rec->co_status);

        // Update: kosongkan co_level dan h2s_level
        $updateRes = $this->actingAs($this->adminUser)
            ->putJson(route('gas-detector.measurements.update', $rec->id), [
                'measurement_date' => $today,
                'well_pad' => 'Well Pad 31',
                'co_level' => null,
                'o2_level' => 20.9,
                'h2s_level' => null,
                'lel_level' => 1.0,
                'pressure' => 14.0,
            ]);

        $updateRes->assertStatus(200)->assertJson(['success' => true]);

        $rec->refresh();
        $this->assertNull($rec->co_level);
        $this->assertNull($rec->h2s_level);
        $this->assertEquals(20.9, $rec->o2_level);
        $this->assertEquals('-', $rec->co_status);
        $this->assertEquals('-', $rec->h2s_status);
        $this->assertEquals('Sesuai', $rec->o2_status);
        $this->assertEquals('Sesuai', $rec->overall_status);
    }
}
