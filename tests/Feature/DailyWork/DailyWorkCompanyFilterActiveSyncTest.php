<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyWorkCompanyFilterActiveSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.sync.filter@geodipa.co.id'],
            [
                'name' => 'Admin Sync Filter Test',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);

        DailyWorkCompany::syncRequired();
    }

    /**
     * Requirement 1 & 5: Backend getActiveForFilter only returns active companies.
     */
    public function test_backend_filter_companies_only_includes_active_companies(): void
    {
        $active = DailyWorkCompany::getActiveForFilter();
        $this->assertNotEmpty($active);

        foreach ($active as $comp) {
            $this->assertTrue((bool) $comp->is_active, "Company {$comp->name} must be active");
            if ($comp->category === DailyWorkCompany::CATEGORY_SUBCON) {
                $this->assertNotNull($comp->parent, "Subcon {$comp->name} must have a parent");
                $this->assertTrue((bool) $comp->parent->is_active, "Parent of subcon {$comp->name} must be active");
            }
        }
    }

    /**
     * Requirement 2 & 7: Deactivating parent hides parent and all its subcons from filter,
     * while (Only) choice and independent companies remain selectable.
     */
    public function test_deactivating_parent_company_hides_parent_and_all_its_subcons_from_filter(): void
    {
        $gdeAll = DailyWorkCompany::where('name', 'GDE (All)')->firstOrFail();
        $gdeOnly = DailyWorkCompany::where('name', 'GDE (Only)')->firstOrFail();
        $kodipa = DailyWorkCompany::where('name', 'KODIPA')->firstOrFail();

        // 1. Deactivate GDE (All)
        $gdeAll->update(['is_active' => false]);

        $filterList = DailyWorkCompany::getActiveForFilter()->pluck('name')->all();

        // GDE (All) and its subcons (KODIPA, GDE (Only)) must NOT be in filter
        $this->assertNotContains('GDE (All)', $filterList);
        $this->assertNotContains('KODIPA', $filterList);
        $this->assertNotContains('GDE (Only)', $filterList);

        // Independent companies like TDP (BUJP) remain in filter
        $this->assertContains('TDP (BUJP)', $filterList);

        // 2. Deactivate JOTRE (All)
        $jotreAll = DailyWorkCompany::where('name', 'JOTRE (All)')->firstOrFail();
        $jotreOnly = DailyWorkCompany::where('name', 'JOTRE (Only)')->firstOrFail();
        $pjse = DailyWorkCompany::where('name', 'PJSE')->firstOrFail();
        $adp = DailyWorkCompany::where('name', 'ADP')->firstOrFail();
        $pjseUtility = DailyWorkCompany::where('name', 'PJSE (Utility)')->firstOrFail();

        $jotreAll->update(['is_active' => false]);

        $filterListAfterJotre = DailyWorkCompany::getActiveForFilter()->pluck('name')->all();

        // JOTRE (All) and all its subcons must NOT be in filter
        $this->assertNotContains('JOTRE (All)', $filterListAfterJotre);
        $this->assertNotContains('JOTRE (Only)', $filterListAfterJotre);
        $this->assertNotContains('PJSE', $filterListAfterJotre);
        $this->assertNotContains('ADP', $filterListAfterJotre);
        $this->assertNotContains('SOKKO', $filterListAfterJotre);
        $this->assertNotContains('BISMO', $filterListAfterJotre);
        $this->assertNotContains('WCP', $filterListAfterJotre);

        // Independent company PJSE (Utility) remains in filter
        $this->assertContains('PJSE (Utility)', $filterListAfterJotre);
    }

    /**
     * Requirement 3: Deactivating single subcon hides only that subcon; parent and other subcons stay active.
     */
    public function test_deactivating_single_subcon_hides_only_that_subcon(): void
    {
        $jotreAll = DailyWorkCompany::where('name', 'JOTRE (All)')->firstOrFail();
        $jotreAll->update(['is_active' => true]);

        $pjse = DailyWorkCompany::where('name', 'PJSE')->firstOrFail();
        $pjse->update(['is_active' => false]);

        $filterList = DailyWorkCompany::getActiveForFilter()->pluck('name')->all();

        // PJSE is excluded
        $this->assertNotContains('PJSE', $filterList);

        // Parent and other subcons remain selectable
        $this->assertContains('JOTRE (All)', $filterList);
        $this->assertContains('ADP', $filterList);
        $this->assertContains('SOKKO', $filterList);
        $this->assertContains('BISMO', $filterList);
        $this->assertContains('WCP', $filterList);
    }

    /**
     * Requirement 4: Reactivating company restores it to filter according to relations.
     */
    public function test_reactivating_company_restores_it_to_filter(): void
    {
        $gdeAll = DailyWorkCompany::where('name', 'GDE (All)')->firstOrFail();
        $kodipa = DailyWorkCompany::where('name', 'KODIPA')->firstOrFail();

        // Deactivate parent
        $gdeAll->update(['is_active' => false]);
        $this->assertNotContains('GDE (All)', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());
        $this->assertNotContains('KODIPA', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());

        // Reactivate parent
        $gdeAll->update(['is_active' => true]);
        $this->assertContains('GDE (All)', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());
        $this->assertContains('KODIPA', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());

        // Deactivate subcon directly
        $kodipa->update(['is_active' => false]);
        $this->assertContains('GDE (All)', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());
        $this->assertNotContains('KODIPA', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());

        // Reactivate subcon
        $kodipa->update(['is_active' => true]);
        $this->assertContains('KODIPA', DailyWorkCompany::getActiveForFilter()->pluck('name')->all());
    }

    /**
     * Requirement 5: Controller endpoints return filter_companies backend collection.
     */
    public function test_controller_endpoints_expose_filter_companies(): void
    {
        $pjse = DailyWorkCompany::where('name', 'PJSE')->firstOrFail();

        // Toggle PJSE via API
        $respToggle = $this->actingAs($this->adminUser)->postJson(route('daily-work.masters.companies.toggle', $pjse->id), [
            'is_active' => false,
        ]);

        $respToggle->assertStatus(200);
        $respToggle->assertJsonPath('is_active', false);
        $filterNames = collect($respToggle->json('filter_companies'))->pluck('name')->all();
        $this->assertNotContains('PJSE', $filterNames);

        // Masters index endpoint
        $respMasters = $this->actingAs($this->adminUser)->getJson(route('daily-work.masters.index'));
        $respMasters->assertStatus(200);
        $mastersFilterNames = collect($respMasters->json('filter_companies'))->pluck('name')->all();
        $this->assertNotContains('PJSE', $mastersFilterNames);

        // Reactivate via API
        $respReactivate = $this->actingAs($this->adminUser)->postJson(route('daily-work.masters.companies.toggle', $pjse->id), [
            'is_active' => true,
        ]);
        $respReactivate->assertStatus(200);
        $reactivatedFilterNames = collect($respReactivate->json('filter_companies'))->pluck('name')->all();
        $this->assertContains('PJSE', $reactivatedFilterNames);
    }

    /**
     * Requirement 5 & 6: Inactive companies cannot be applied as active filter in backend queries.
     */
    public function test_inactive_company_cannot_be_applied_as_filter_in_backend_query(): void
    {
        $today = Carbon::today()->toDateString();

        $pjse = DailyWorkCompany::where('name', 'PJSE')->firstOrFail();
        $adp = DailyWorkCompany::where('name', 'ADP')->firstOrFail();
        $pjse->update(['is_active' => false]);
        $adp->update(['is_active' => true]);

        $workPjse = DailyWork::create([
            'name' => 'Work PJSE Today',
            'unit' => 'PJSE',
            'location' => 'Turbine Area',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'Sedang Berlangsung',
        ]);

        $workAdp = DailyWork::create([
            'name' => 'Work ADP Today',
            'unit' => 'ADP',
            'location' => 'Cooling Tower',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'Sedang Berlangsung',
        ]);

        // When query with company=PJSE,ADP where PJSE is inactive:
        // Backend strips PJSE and filters strictly by ADP
        $resp = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'today',
            'date' => $today,
            'company' => 'PJSE,ADP',
        ]));

        $resp->assertStatus(200);
        $works = collect($resp->json('works'));
        $this->assertTrue($works->contains('id', $workAdp->id));
        $this->assertFalse($works->contains('id', $workPjse->id));
        $this->assertEquals(1, $resp->json('stats.companies_today'));
        $this->assertEquals('ADP', $resp->json('stats.companies_list_today.0.name'));
    }

    /**
     * Requirement 8: Deactivated company records are NEVER deleted and remain visible in history & detail.
     */
    public function test_historical_work_records_with_inactive_company_remain_intact_and_visible(): void
    {
        $today = Carbon::today()->toDateString();

        $pjse = DailyWorkCompany::where('name', 'PJSE')->firstOrFail();
        $pjse->update(['is_active' => false]);

        $work = DailyWork::create([
            'name' => 'Historical Work With Inactive PJSE',
            'unit' => 'PJSE',
            'location' => 'Turbine Area',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'Selesai',
        ]);

        // Detail endpoint must return company correctly
        $respShow = $this->actingAs($this->adminUser)->getJson(route('daily-work.show', $work->id));
        $respShow->assertStatus(200);
        $respShow->assertJsonPath('work.unit', 'PJSE');

        // History mode without company filter displays the historical work
        $respHistory = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'all',
        ]));
        $respHistory->assertStatus(200);
        $historyWorks = collect($respHistory->json('works'));
        $this->assertTrue($historyWorks->contains('id', $work->id));
    }

    /**
     * Requirement 1, 5, 6: Blade view script contains active company filtering and cleanup logic.
     */
    public function test_view_contains_filter_synchronization_and_state_cleanup_scripts(): void
    {
        $resp = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $resp->assertStatus(200);

        // Script variables & helpers
        $resp->assertSee('filterCompanies', false);
        $resp->assertSee('getFilterEligibleCompanies', false);
        $resp->assertSee('syncFilterCompanyStateWithActive', false);
        $resp->assertSee('getCompanyHierarchy(true)', false);
    }
}
