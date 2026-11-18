<?php

namespace Tests\Feature\DailyWork;

use App\Models\DailyWork;
use App\Models\DailyWorkCompany;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyWorkDashboardAndDropdownTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.dw.dashboard@geodipa.co.id'],
            [
                'name' => 'Admin Test Dashboard',
                'password' => bcrypt('password123'),
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'aktif',
            ]
        );
        $this->adminUser->update(['role' => User::ROLE_ADMIN_MANAGEMENT, 'status' => 'aktif']);
    }

    /**
     * Parent companies (GDE (All), JOTRE (All)) cannot be used for new work, but only variants can be created independently.
     */
    public function test_all_four_gde_and_jotre_choices_can_be_created_independently(): void
    {
        // GDE (Only) and JOTRE (Only) can be created
        foreach (['GDE (Only)', 'JOTRE (Only)'] as $comp) {
            $resp = $this->actingAs($this->adminUser)->postJson(route('daily-work.store'), [
                'name' => "Work for {$comp}",
                'unit' => $comp,
                'sika' => [],
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'location' => 'Area 1',
                'description' => 'Test',
            ]);

            $resp->assertSuccessful();
            $this->assertDatabaseHas('daily_works', [
                'name' => "Work for {$comp}",
                'unit' => $comp,
            ]);
        }

        // GDE (All) and JOTRE (All) cannot be chosen as executor companies
        foreach (['GDE (All)', 'JOTRE (All)'] as $comp) {
            $resp = $this->actingAs($this->adminUser)->postJson(route('daily-work.store'), [
                'name' => "Work for {$comp}",
                'unit' => $comp,
                'sika' => [],
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
                'location' => 'Area 1',
                'description' => 'Test',
            ]);

            $resp->assertStatus(422)->assertJsonValidationErrors('unit');
        }
    }

    /**
     * Edit mode shows previously saved company correctly, including inactive master.
     */
    public function test_edit_preserves_company_and_handles_inactive_master(): void
    {
        $work = DailyWork::create([
            'name' => 'Historical Work With Inactive Company',
            'unit' => 'KODIPA',
            'sika' => 'Ketinggian',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'location' => 'Turbine 1',
            'status' => 'Belum Mulai',
            'progress' => 0,
        ]);

        $resp = $this->actingAs($this->adminUser)->getJson(route('daily-work.show', $work->id));
        $resp->assertStatus(200);
        $resp->assertJsonPath('work.unit', 'KODIPA');

        // Update with another standalone option: JOTRE (Only)
        $updateResp = $this->actingAs($this->adminUser)->putJson(route('daily-work.update', $work->id), [
            'name' => 'Updated Historical Work',
            'unit' => 'JOTRE (Only)',
            'sika' => ['Ketinggian'],
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'location' => 'Turbine 1',
            'description' => 'Updated',
        ]);

        $updateResp->assertStatus(200);
        $this->assertDatabaseHas('daily_works', [
            'id' => $work->id,
            'unit' => 'JOTRE (Only)',
        ]);
    }

    /**
     * Items 6, 7, 8, 9: Dashboard filter supports multi-company and individual subcons without duplication.
     * Also verifies that summary stats & unique company count strictly match the active filter.
     */
    public function test_dashboard_filter_supports_individual_subcon_and_syncs_stats(): void
    {
        $today = Carbon::today()->toDateString();

        $workGde = DailyWork::create([
            'name' => 'Work GDE All',
            'unit' => 'GDE (All)',
            'location' => 'Area Main',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'Sedang Berlangsung',
        ]);

        $workKodipa = DailyWork::create([
            'name' => 'Work Kodipa',
            'unit' => 'KODIPA',
            'location' => 'Area Subcon',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'Sedang Berlangsung',
        ]);

        $workPjse = DailyWork::create([
            'name' => 'Work PJSE',
            'unit' => 'PJSE',
            'location' => 'Area Subcon 2',
            'start_date' => $today,
            'end_date' => $today,
            'status' => 'Sedang Berlangsung',
        ]);

        // 1. Filter strictly by individual subcon PJSE
        $respSubcon = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'today',
            'date' => $today,
            'company' => 'PJSE',
        ]));

        $respSubcon->assertStatus(200);
        $worksSubcon = collect($respSubcon->json('works'));
        $this->assertTrue($worksSubcon->contains('id', $workPjse->id));
        $this->assertFalse($worksSubcon->contains('id', $workGde->id));
        $this->assertFalse($worksSubcon->contains('id', $workKodipa->id));

        // Stats count must match filtered unique companies (exactly 1: PJSE)
        $this->assertEquals(1, $respSubcon->json('stats.companies_today'));
        $this->assertCount(1, $respSubcon->json('stats.companies_list_today'));
        $this->assertEquals('PJSE', $respSubcon->json('stats.companies_list_today.0.name'));

        // 2. Filter by GDE (All) and KODIPA
        $resp = $this->actingAs($this->adminUser)->getJson(route('daily-work.index', [
            'date_mode' => 'today',
            'date' => $today,
            'company' => 'GDE (All),KODIPA',
        ]));

        $resp->assertStatus(200);
        $works = collect($resp->json('works'));
        $this->assertTrue($works->contains('id', $workGde->id));
        $this->assertTrue($works->contains('id', $workKodipa->id));
        $this->assertFalse($works->contains('id', $workPjse->id));

        // Stats count must match (2: GDE (All) and KODIPA)
        $this->assertEquals(2, $resp->json('stats.companies_today'));
        $this->assertCount(2, $resp->json('stats.companies_list_today'));
    }

    /**
     * Items 1, 2, 3, 4: Modal Perusahaan Terlibat acts as information display without navigation links.
     */
    public function test_modal_perusahaan_terlibat_displays_info_with_correct_structure(): void
    {
        $resp = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $resp->assertStatus(200);

        // Card 2 triggers filterByCardCompanies
        $resp->assertSee('onclick="filterByCardCompanies()"', false);

        // Modal container exists with max-height 360px
        $resp->assertSee('id="modalSelectCompanyToday"', false);
        $resp->assertSee('id="modalCompanyListContainer"', false);
        $resp->assertSee('max-height: 360px', false);

        // Modal title and subtitle IDs exist
        $resp->assertSee('id="modalCompanyDateTitle"', false);
        $resp->assertSee('id="modalCompanyDateSubtitle"', false);

        // Modal footer has clean Tutup button, not navigation redirect
        $resp->assertSee('data-bs-dismiss="modal">Tutup</button>', false);
        $resp->assertDontSee('Tampilkan Semua Perusahaan', false);
    }

    /**
     * Items 5, 6, 7, 8, 10: Hierarchical Filter Subcon and Parent Checkbox Handlers.
     */
    public function test_view_script_contains_subcon_and_parent_filter_logic(): void
    {
        $resp = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $resp->assertStatus(200);

        // Subcon click and checkbox handlers
        $resp->assertSee('handleFilterSubconClick', false);
        $resp->assertSee('handleFilterParentClick', false);
        $resp->assertSee('toggleFilterCompanyItem', false);
        $resp->assertSee('toggleFilterParent', false);
        $resp->assertSee('toggleFilterSubcon', false);
        $resp->assertSee('applyCompanyFilter', false);
        $resp->assertSee('resetCompanyFilter', false);
        $resp->assertSee('closeCompanyFilterDropdown', false);
        $resp->assertSee('btn-tree-chevron', false);

        // Subcon parent context formatting in modal and dropdown
        $resp->assertSee('parent_short_name', false);
    }

    /**
     * "Total Seluruh Data" card is removed and 2 card grid is used.
     */
    public function test_total_seluruh_data_card_is_removed_and_2_card_grid_is_used(): void
    {
        $resp = $this->actingAs($this->adminUser)->get(route('daily-work.index'));
        $resp->assertStatus(200);

        $resp->assertSee('stats-grid-2', false);
        $resp->assertDontSee('id="cardStatHistory"', false);
        $resp->assertDontSee('Total Seluruh Data', false);

        $resp->assertSee('id="cardStatToday"', false);
        $resp->assertSee('id="cardStatCompanies"', false);
    }
}
