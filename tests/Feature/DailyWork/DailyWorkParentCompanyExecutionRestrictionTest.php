<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyWorkParentCompanyExecutionRestrictionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        DailyWorkCompany::ensureRequired();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.dw@example.com'],
            ['name' => 'Admin Daily Work', 'password' => bcrypt('password')]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);
    }

    protected function workPayload(string $unit, array $overrides = []): array
    {
        return array_merge([
            'name' => "Pekerjaan {$unit}",
            'unit' => $unit,
            'sika' => [],
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'location' => 'Area Proyek',
            'description' => "Deskripsi pekerjaan untuk {$unit}",
        ], $overrides);
    }

    public function test_cannot_create_work_with_gde_all(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('daily-work.store'), $this->workPayload('GDE (All)'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->assertDatabaseMissing('daily_works', [
            'name' => 'Pekerjaan GDE (All)',
        ]);
    }

    public function test_cannot_create_work_with_jotre_all(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('daily-work.store'), $this->workPayload('JOTRE (All)'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->assertDatabaseMissing('daily_works', [
            'name' => 'Pekerjaan JOTRE (All)',
        ]);
    }

    public function test_can_create_work_with_eligible_companies(): void
    {
        $allowedCompanies = [
            'GDE (Only)',
            'KODIPA',
            'JOTRE (Only)',
            'PJSE',
            'ADP',
            'SOKKO',
            'BISMO',
            'WCP',
            'TDP (BUJP)',
            'PJSE (Utility)',
            'Rentokil (Pest Control)',
            'PMC',
            'Bhumi Phala (Maintenance EWS)',
            'Environesia',
        ];

        foreach ($allowedCompanies as $comp) {
            $response = $this->actingAs($this->adminUser)
                ->postJson(route('daily-work.store'), $this->workPayload($comp));

            $response->assertStatus(201);
            $this->assertDatabaseHas('daily_works', [
                'name' => "Pekerjaan {$comp}",
                'unit' => $comp,
            ]);
        }
    }

    public function test_historical_work_with_gde_all_is_preserved_when_edited(): void
    {
        $gdeAll = DailyWorkCompany::where('name', 'GDE (All)')->first();

        // Simulate existing historical work with GDE (All)
        $work = DailyWork::create([
            'work_code' => 'DW-HIST-01',
            'name' => 'Historical Work GDE All',
            'unit' => 'GDE (All)',
            'company_id' => $gdeAll?->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'location' => 'Area Lama',
            'description' => 'Historis',
            'status' => 'belum',
            'sika' => [],
        ]);

        // Edit description/name without changing unit -> must succeed and preserve GDE (All)
        $response = $this->actingAs($this->adminUser)
            ->putJson(route('daily-work.update', $work->id), [
                'name' => 'Updated Historical Work GDE All',
                'unit' => 'GDE (All)',
                'sika' => [],
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'location' => 'Area Baru',
                'description' => 'Updated historis',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('daily_works', [
            'id' => $work->id,
            'name' => 'Updated Historical Work GDE All',
            'unit' => 'GDE (All)',
        ]);
    }

    public function test_cannot_update_work_to_gde_all_or_jotre_all_if_changed(): void
    {
        $kodipa = DailyWorkCompany::where('name', 'KODIPA')->first();

        $work = DailyWork::create([
            'work_code' => 'DW-TEST-02',
            'name' => 'Active Work Kodipa',
            'unit' => 'KODIPA',
            'company_id' => $kodipa?->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'location' => 'Area Lama',
            'description' => 'Existing',
            'status' => 'belum',
            'sika' => [],
        ]);

        // Try changing to GDE (All) -> must be rejected
        $response = $this->actingAs($this->adminUser)
            ->putJson(route('daily-work.update', $work->id), [
                'name' => 'Active Work Kodipa',
                'unit' => 'GDE (All)',
                'sika' => [],
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'location' => 'Area Lama',
                'description' => 'Existing',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        // Try changing to JOTRE (All) -> must be rejected
        $response2 = $this->actingAs($this->adminUser)
            ->putJson(route('daily-work.update', $work->id), [
                'name' => 'Active Work Kodipa',
                'unit' => 'JOTRE (All)',
                'sika' => [],
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'location' => 'Area Lama',
                'description' => 'Existing',
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors('unit');
    }

    public function test_dashboard_filter_still_contains_and_filters_by_gde_all_and_jotre_all(): void
    {
        $gdeAll = DailyWorkCompany::where('name', 'GDE (All)')->first();

        DailyWork::create([
            'work_code' => 'DW-HIST-03',
            'name' => 'Historical Filter Work GDE All',
            'unit' => 'GDE (All)',
            'company_id' => $gdeAll?->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'location' => 'Area Lama',
            'description' => 'Historis',
            'status' => 'belum',
            'sika' => [],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('daily-work.index', ['company' => 'GDE (All)']));

        $response->assertOk();
        $response->assertSee('GDE (All)');
        $response->assertSee('Historical Filter Work GDE All');
    }

    public function test_master_perusahaan_still_manages_gde_all_and_jotre_all(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('daily-work.masters.index'));

        $response->assertOk();
        $companies = $response->json('companies');
        $names = collect($companies)->pluck('name')->all();

        $this->assertContains('GDE (All)', $names);
        $this->assertContains('JOTRE (All)', $names);

        // Master can add subcon under GDE (All)
        $gdeAll = DailyWorkCompany::where('name', 'GDE (All)')->first();
        $createSubcon = $this->actingAs($this->adminUser)
            ->postJson(route('daily-work.masters.companies.store'), [
                'name' => 'Subcon Baru GDE',
                'has_parent' => true,
                'parent_company_id' => $gdeAll->id,
            ]);

        $createSubcon->assertOk();
        $this->assertDatabaseHas('daily_work_companies', [
            'name' => 'Subcon Baru GDE',
            'parent_company_id' => $gdeAll->id,
        ]);
    }
}
