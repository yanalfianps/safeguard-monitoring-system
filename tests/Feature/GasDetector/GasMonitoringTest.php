<?php

namespace Tests\Feature\GasDetector;

use App\Models\ActivityLog;
use App\Models\GasMonitoring;
use App\Models\User;
use App\Models\WellPad;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GasMonitoringTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $regularUser;
    protected User $adminInventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['email' => 'admin.gas@geodipa.co.id'],
            [
                'name' => 'Admin Gas Dieng',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]
        );
        $this->user->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'Aktif']);

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user.gas@geodipa.co.id'],
            [
                'name' => 'User Gas Dieng',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_USER,
                'status' => 'Aktif',
            ]
        );
        $this->regularUser->update(['role' => User::ROLE_USER, 'status' => 'Aktif']);

        $this->adminInventory = User::firstOrCreate(
            ['email' => 'inventory.gas@geodipa.co.id'],
            [
                'name' => 'Admin Inventory Gas',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_INVENTORY,
                'status' => 'Aktif',
            ]
        );
        $this->adminInventory->update(['role' => User::ROLE_ADMIN_INVENTORY, 'status' => 'Aktif']);
    }

    public function test_gas_monitoring_index_renders_with_5_parameter_cards_and_filters(): void
    {
        $response = $this->actingAs($this->user)->get(route('gas-detector.index'));

        $response->assertStatus(200);
        $response->assertSee('Gas Monitoring');
        $response->assertSee('Tanggal Data yang Ditampilkan');
        $response->assertSee('Tambah Data Gas Monitoring');
        $response->assertSee('Kelola Well Pad');
        $response->assertSee('CO (ppm)');
        $response->assertSee('O₂ (%)');
        $response->assertSee('H₂S (ppm)');
        $response->assertSee('LEL (%LEL)');
        $response->assertSee('Pressure (barg)');
        $response->assertSee('NAB');
        $response->assertSee('Well Pad 7');
        $response->assertSee('Well Pad 28');
    }

    public function test_gas_monitoring_supports_all_six_period_filters(): void
    {
        $today = Carbon::today()->toDateString();

        $modes = [
            ['period_type' => 'all'],
            ['period_type' => 'daily', 'filter_date' => $today],
            ['period_type' => 'weekly', 'filter_week_date' => $today],
            ['period_type' => 'monthly', 'filter_month' => substr($today, 0, 7)],
            ['period_type' => 'yearly', 'filter_year' => substr($today, 0, 4)],
            ['period_type' => 'custom', 'start_date' => Carbon::today()->subDays(7)->toDateString(), 'end_date' => $today],
        ];

        foreach ($modes as $query) {
            $res = $this->actingAs($this->user)->get(route('gas-detector.index', $query));
            $res->assertStatus(200);
            $res->assertSee('Gas Monitoring');
        }
    }

    public function test_can_batch_create_single_view_edit_and_delete_measurement_with_5_parameters(): void
    {
        WellPad::ensureDefaultsSeeded();
        $wp7 = WellPad::where('name', 'Well Pad 7')->first();
        $wp28 = WellPad::where('name', 'Well Pad 28')->first();

        $batchDate = Carbon::today()->toDateString();

        // 1. Batch Create (Well Pad 7 normal, Well Pad 28 exceeds H2S & O2 low)
        $batchPayload = [
            'measurement_date' => $batchDate,
            'measurements' => [
                [
                    'well_pad_id' => $wp7->id,
                    'well_pad_name' => 'Well Pad 7',
                    'co_level' => 12.5,
                    'o2_level' => 20.9,
                    'h2s_level' => 4.0,
                    'lel_level' => 2.0,
                    'pressure' => 16.5,
                    'notes' => 'Normal operasi WP 7',
                ],
                [
                    'well_pad_id' => $wp28->id,
                    'well_pad_name' => 'Well Pad 28',
                    'co_level' => 15.0,
                    'o2_level' => 19.0, // < 19.5 => Tidak Sesuai
                    'h2s_level' => 12.5, // > 10.0 => Tidak Sesuai
                    'lel_level' => 5.0,
                    'pressure' => 18.0,
                    'notes' => 'Peringatan H2S & O2 di WP 28',
                ],
            ],
        ];

        $batchRes = $this->actingAs($this->user)
            ->postJson(route('gas-detector.measurements.store-batch'), $batchPayload);

        $batchRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $m7 = GasMonitoring::where('well_pad', 'Well Pad 7')
            ->whereDate('measurement_date', $batchDate)
            ->latest('id')
            ->first();

        $m28 = GasMonitoring::where('well_pad', 'Well Pad 28')
            ->whereDate('measurement_date', $batchDate)
            ->latest('id')
            ->first();

        $this->assertNotNull($m7);
        $this->assertEquals('Sesuai', $m7->overall_status);
        $this->assertEquals('Sesuai', $m7->o2_status);
        $this->assertEquals('Sesuai', $m7->lel_status);

        $this->assertNotNull($m28);
        $this->assertEquals('Tidak Sesuai', $m28->o2_status);
        $this->assertEquals('Tidak Sesuai', $m28->h2s_status);
        $this->assertEquals('Tidak Sesuai', $m28->overall_status);

        // 2. Show single measurement detail JSON
        $detailRes = $this->actingAs($this->user)
            ->getJson(route('gas-detector.measurements.show', $m28->id));

        $detailRes->assertStatus(200)
            ->assertJsonPath('data.well_pad', 'Well Pad 28')
            ->assertJsonPath('data.overall_status', 'Tidak Sesuai');

        // 3. Update single measurement to normal values
        $updateRes = $this->actingAs($this->user)
            ->putJson(route('gas-detector.measurements.update', $m28->id), [
                'measurement_date' => $batchDate,
                'well_pad' => 'Well Pad 28',
                'co_level' => 10.0,
                'o2_level' => 20.8,
                'h2s_level' => 5.0,
                'lel_level' => 3.0,
                'pressure' => 16.0,
                'notes' => 'Sudah dinormalisasi',
            ]);

        $updateRes->assertStatus(200)->assertJson(['success' => true]);
        $m28->refresh();
        $this->assertEquals('Sesuai', $m28->o2_status);
        $this->assertEquals('Sesuai', $m28->h2s_status);
        $this->assertEquals('Sesuai', $m28->overall_status);

        // 4. Delete single measurement
        $delRes = $this->actingAs($this->user)
            ->deleteJson(route('gas-detector.measurements.destroy', $m28->id));

        $delRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('gas_monitorings', ['id' => $m28->id]);
    }

    public function test_well_pad_management_crud_and_safe_deactivation(): void
    {
        // 1. Load Well Pads JSON
        $listRes = $this->actingAs($this->user)->getJson(route('gas-detector.well-pads.data'));
        $listRes->assertStatus(200)->assertJson(['success' => true]);

        // 2. Create new Well Pad
        $createRes = $this->actingAs($this->user)->postJson(route('gas-detector.well-pads.store'), [
            'name' => 'Well Pad 99 Test',
            'code' => 'WP-99T',
            'is_active' => true,
        ]);
        $createRes->assertStatus(201)->assertJson(['success' => true]);
        $wpId = $createRes->json('data.id');

        // 3. Update Well Pad
        $updateRes = $this->actingAs($this->user)->putJson(route('gas-detector.well-pads.update', $wpId), [
            'name' => 'Well Pad 99 Updated',
            'code' => 'WP-99U',
            'is_active' => true,
        ]);
        $updateRes->assertStatus(200)->assertJson(['success' => true]);

        // 4. Delete empty Well Pad -> permanently deleted
        $delRes = $this->actingAs($this->user)->deleteJson(route('gas-detector.well-pads.destroy', $wpId));
        $delRes->assertStatus(200)->assertJson(['success' => true, 'action' => 'deleted']);
    }

    public function test_gas_monitoring_export_pdf_and_role_permissions(): void
    {
        // Admin Management can export PDF
        $response = $this->actingAs($this->user)
            ->get(route('gas-detector.measurements.export', [
                'period_type' => 'monthly',
                'filter_month' => date('Y-m'),
                'well_pad' => 'all',
            ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));

        // Regular User cannot create batch measurements
        $forbiddenRes = $this->actingAs($this->regularUser)
            ->postJson(route('gas-detector.measurements.store-batch'), [
                'measurement_date' => date('Y-m-d'),
                'measurements' => [],
            ]);
        $forbiddenRes->assertStatus(403);
    }
}
