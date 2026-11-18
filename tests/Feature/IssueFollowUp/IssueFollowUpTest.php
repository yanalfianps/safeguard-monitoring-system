<?php

namespace Tests\Feature\IssueFollowUp;

use App\Models\ActivityLog;
use App\Models\FindingSource;
use App\Models\IssueDocumentation;
use App\Models\IssueFollowUp;
use App\Models\IssueFollowUpLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueFollowUpTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAuthUser(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_issue_follow_up_index_page_renders_with_stats_charts_and_latest_highlights(): void
    {
        $user = $this->createAuthUser('admin');

        $overdueIssue = IssueFollowUp::create([
            'title' => 'Temuan Kabel Terkelupas di Area Wellpad',
            'hsse_division' => 'H',
            'category' => 'Audit GIMS',
            'unit' => 'Dieng Unit 1',
            'reported_date' => Carbon::today()->subDays(10)->toDateString(),
            'due_date' => Carbon::today()->subDays(2)->toDateString(),
            'location' => 'Wellpad HCE-7',
            'description' => 'Isolasi kabel power lampu sorot terkelupas.',
            'action_plan' => 'Ganti isolasi kabel dan pasang conduit pelindung.',
            'pic' => 'Budi Santoso',
            'priority' => 'Tinggi',
            'status' => 'Open',
        ]);

        $response = $this->actingAs($user)->get(route('issue-follow-up.index'));

        $response->assertStatus(200);
        $response->assertSee('Issue &amp; Follow Up', false);
        $response->assertSee('Tambah Temuan');
        $response->assertSee('Kelola Sumber Temuan');
        $response->assertSee('Export PDF');
        $response->assertSee($overdueIssue->title);
        $response->assertSee('Health &amp; Safety', false);
        $response->assertSee('Security');
        $response->assertSee('Environment');
        $response->assertSee('Social');
        $response->assertSee('ringCanvas_H', false);
        $response->assertSee('ringCanvas_S_SECURITY', false);
        $response->assertSee('ringCanvas_E', false);
        $response->assertSee('ringCanvas_S_SOCIAL', false);
    }

    public function test_can_create_new_issue_and_persist_to_database_with_initial_log(): void
    {
        $user = $this->createAuthUser('admin');

        $payload = [
            'reported_date' => Carbon::today()->toDateString(),
            'hsse_division' => 'E',
            'title' => 'Tumpahan Oli Ringan di Area Genset',
            'category' => 'Housekeeping & 5R',
            'unit' => 'Utility',
            'location' => 'Ruang Genset Darurat',
            'description' => 'Terdapat ceceran oli di sekitar pompa transfer.',
            'action_plan' => 'Bersihkan dengan oil absorbent dan periksa seal pompa.',
            'pic' => 'Andi Pratama',
            'due_date' => Carbon::today()->addDays(5)->toDateString(),
            'priority' => 'Sedang',
            'status' => 'Open',
            'notes' => 'Area sudah dipasang safety cone sementara.',
        ];

        $response = $this->actingAs($user)
            ->postJson(route('issue-follow-up.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('issue.hsse_division', 'E')
            ->assertJsonPath('issue.title', 'Tumpahan Oli Ringan di Area Genset')
            ->assertJsonPath('issue.status', 'Open');

        $this->assertDatabaseHas('issue_follow_ups', [
            'title' => 'Tumpahan Oli Ringan di Area Genset',
            'hsse_division' => 'E',
            'unit' => 'Utility',
            'pic' => 'Andi Pratama',
            'status' => 'Open',
        ]);

        $issue = IssueFollowUp::where('title', 'Tumpahan Oli Ringan di Area Genset')->firstOrFail();

        $this->assertDatabaseHas('issue_follow_up_logs', [
            'issue_follow_up_id' => $issue->id,
            'new_status' => 'Open',
            'note' => 'Area sudah dipasang safety cone sementara.',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Issue & Follow Up',
            'action' => 'TAMBAH',
            'record_id' => $issue->id,
        ]);
    }

    public function test_adding_follow_up_automatically_transitions_open_to_in_progress_and_preserves_history(): void
    {
        $user = $this->createAuthUser('user');

        $issue = IssueFollowUp::create([
            'title' => 'APAR Kedaluwarsa di Gudang B3',
            'hsse_division' => 'S',
            'category' => 'APD & Peralatan Keselamatan',
            'unit' => 'PMU',
            'reported_date' => Carbon::today()->subDays(3)->toDateString(),
            'due_date' => Carbon::today()->addDays(4)->toDateString(),
            'description' => '1 tabung APAR CO2 masa berlaku habis.',
            'pic' => 'Rizal HSSE',
            'status' => 'Open',
        ]);

        IssueFollowUpLog::create([
            'issue_follow_up_id' => $issue->id,
            'old_status' => null,
            'new_status' => 'Open',
            'note' => 'Temuan awal dicatat.',
            'user_name' => $user->name,
            'followed_up_at' => now()->subDay(),
        ]);

        // Tambah tindak lanjut pertama tanpa memilih Closed -> otomatis beralih dari Open ke In Progress
        $res1 = $this->actingAs($user)
            ->postJson(route('issue-follow-up.follow-up', $issue->id), [
                'note' => 'Tabung APAR sedang dikirim untuk pengisian ulang.',
                'status' => 'Open',
            ]);

        $res1->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('issue.status', 'In Progress');

        // Tambah tindak lanjut kedua -> riwayat lama tidak tertimpa
        $res2 = $this->actingAs($user)
            ->postJson(route('issue-follow-up.follow-up', $issue->id), [
                'note' => 'Tabung APAR pengganti telah dipasang di Gudang B3.',
                'status' => 'In Progress',
            ]);

        $res2->assertStatus(201);

        $logs = IssueFollowUpLog::where('issue_follow_up_id', $issue->id)->orderBy('id')->get();
        $this->assertCount(3, $logs);
        $this->assertEquals('Temuan awal dicatat.', $logs[0]->note);
        $this->assertEquals('Tabung APAR sedang dikirim untuk pengisian ulang.', $logs[1]->note);
        $this->assertEquals('Tabung APAR pengganti telah dipasang di Gudang B3.', $logs[2]->note);
    }

    public function test_can_close_and_reopen_issue_with_status_logging(): void
    {
        $user = $this->createAuthUser('admin');

        $issue = IssueFollowUp::create([
            'title' => 'Rambu Evakuasi Pudar',
            'hsse_division' => 'S',
            'category' => 'Safety Inspection',
            'unit' => 'Dieng Unit 2',
            'reported_date' => Carbon::today()->subDays(5)->toDateString(),
            'due_date' => Carbon::today()->subDay()->toDateString(),
            'description' => 'Rambu jalur evakuasi di titik kumpul pudar.',
            'pic' => 'Dedi Kurniawan',
            'status' => 'In Progress',
        ]);

        $this->assertTrue($issue->isOverdue());

        // Close issue
        $closeResponse = $this->actingAs($user)
            ->patchJson(route('issue-follow-up.close', $issue->id), [
                'note' => 'Rambu evakuasi baru telah terpasang dan diverifikasi.',
            ]);

        $closeResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('issue.status', 'Closed');

        $issue->refresh();
        $this->assertEquals('Closed', $issue->status);
        $this->assertNotNull($issue->closed_date);
        $this->assertFalse($issue->isOverdue());

        // Reopen issue
        $reopenResponse = $this->actingAs($user)
            ->patchJson(route('issue-follow-up.reopen', $issue->id), [
                'status' => 'In Progress',
                'note' => 'Diperlukan penambahan stiker reflektif malam hari.',
            ]);

        $reopenResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('issue.status', 'In Progress');

        $issue->refresh();
        $this->assertEquals('In Progress', $issue->status);
        $this->assertNull($issue->closed_date);
    }

    public function test_can_view_detail_edit_filter_and_delete_issue(): void
    {
        $user = $this->createAuthUser('admin');

        $issue = IssueFollowUp::create([
            'title' => 'Penerangan Kurang di Tangga Darurat',
            'hsse_division' => 'S',
            'category' => 'Peralatan & Utilitas',
            'unit' => 'JOTRE',
            'reported_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(3)->toDateString(),
            'description' => 'Lampu TL tangga darurat mati 1 titik.',
            'pic' => 'Hendra Listrik',
            'status' => 'Open',
        ]);

        // Show page
        $this->actingAs($user)
            ->get(route('issue-follow-up.show', $issue->id))
            ->assertOk()
            ->assertSee($issue->issue_code)
            ->assertSee('Penerangan Kurang di Tangga Darurat');

        // Edit page
        $this->actingAs($user)
            ->get(route('issue-follow-up.edit', $issue->id))
            ->assertOk()
            ->assertSee($issue->issue_code);

        // Update
        $this->actingAs($user)
            ->putJson(route('issue-follow-up.update', $issue->id), [
                'reported_date' => Carbon::today()->toDateString(),
                'hsse_division' => 'S',
                'title' => 'Penerangan Tangga Darurat Diperbaiki',
                'category' => 'Peralatan & Utilitas',
                'unit' => 'JOTRE',
                'location' => 'Gedung Kontrol Lantai 2',
                'description' => 'Lampu LED baru sedang dipasang.',
                'action_plan' => 'Ganti dengan lampu LED 18W.',
                'pic' => 'Hendra Listrik',
                'due_date' => Carbon::today()->addDays(3)->toDateString(),
                'priority' => 'Tinggi',
                'status' => 'In Progress',
                'notes' => 'Penggantian lampu selesai 80%.',
            ])
            ->assertOk()
            ->assertJsonPath('issue.title', 'Penerangan Tangga Darurat Diperbaiki')
            ->assertJsonPath('issue.status', 'In Progress');

        // Filter via AJAX
        $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['unit' => 'JOTRE', 'status' => 'In Progress']))
            ->assertOk()
            ->assertJsonPath('success', true);

        // Delete
        $this->actingAs($user)
            ->deleteJson(route('issue-follow-up.destroy', $issue->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('issue_follow_ups', ['id' => $issue->id]);
    }

    public function test_dashboard_displays_issue_follow_up_summary_and_latest_issues(): void
    {
        $user = $this->createAuthUser('admin');

        IssueFollowUp::create([
            'title' => 'Temuan Inspeksi Pipa Uap',
            'hsse_division' => 'S',
            'category' => 'Safety Inspection',
            'unit' => 'Dieng Unit 1',
            'reported_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(7)->toDateString(),
            'description' => 'Isolasi termal pipa uap perlu peremajaan.',
            'pic' => 'Tim Maintenance',
            'status' => 'Open',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Total Temuan');
        $response->assertSee('Temuan Open');
        $response->assertSee('Temuan Inspeksi Pipa Uap');
    }

    public function test_active_highlight_lifecycle_open_in_progress_closed_and_reopen_with_date_ordering(): void
    {
        $user = $this->createAuthUser('admin');

        // Buat 2 temuan aktif dengan tanggal berbeda untuk menguji urutan reported_date DESC
        $olderOpen = IssueFollowUp::create([
            'title' => 'Temuan Aktif Lama (Open)',
            'hsse_division' => 'H',
            'category' => 'Safety Inspection',
            'unit' => 'PMU',
            'reported_date' => Carbon::today()->subDays(5)->toDateString(),
            'due_date' => Carbon::today()->addDays(5)->toDateString(),
            'description' => 'Temuan aktif tanggal lebih lama.',
            'pic' => 'PIC Satu',
            'status' => 'Open',
        ]);

        $newerInProgress = IssueFollowUp::create([
            'title' => 'Temuan Aktif Baru (In Progress)',
            'hsse_division' => 'E',
            'category' => 'Housekeeping & 5R',
            'unit' => 'Utility',
            'reported_date' => Carbon::today()->subDays(1)->toDateString(),
            'due_date' => Carbon::today()->addDays(5)->toDateString(),
            'description' => 'Temuan aktif tanggal lebih baru.',
            'pic' => 'PIC Dua',
            'status' => 'In Progress',
        ]);

        // 1. Kedua temuan aktif (Open & In Progress) muncul di highlight JSON, diurutkan reported_date DESC
        $indexJson1 = $this->actingAs($user)->getJson(route('issue-follow-up.index'))->assertOk();
        $highlightIds1 = collect($indexJson1->json('latest_highlights'))->pluck('id')->all();

        $this->assertContains($olderOpen->id, $highlightIds1);
        $this->assertContains($newerInProgress->id, $highlightIds1);
        $this->assertLessThan(
            array_search($olderOpen->id, $highlightIds1, true),
            array_search($newerInProgress->id, $highlightIds1, true),
            'Temuan dengan reported_date lebih baru harus berada di urutan lebih awal pada highlight.'
        );

        // 2. Ubah status $newerInProgress menjadi Closed melalui endpoint close
        $closeRes = $this->actingAs($user)->postJson(route('issue-follow-up.close', $newerInProgress->id), [
            'note' => 'Perbaikan telah diselesaikan dan diverifikasi.',
        ]);

        $closeRes->assertOk()
            ->assertJsonPath('issue.status', 'Closed');

        // 3. Temuan Closed langsung hilang dari highlight JSON
        $highlightIdsAfterClose = collect($closeRes->json('latest_highlights'))->pluck('id')->all();
        $this->assertNotContains($newerInProgress->id, $highlightIdsAfterClose);
        $this->assertContains($olderOpen->id, $highlightIdsAfterClose);

        // 4. Temuan Closed TIDAK dihapus dari database, tetap tersedia di tabel utama, filter Closed, dan halaman detail
        $this->assertDatabaseHas('issue_follow_ups', [
            'id' => $newerInProgress->id,
            'status' => 'Closed',
        ]);

        $closedFilterJson = $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['status' => 'Closed']))
            ->assertOk();
        $this->assertContains($newerInProgress->id, collect($closedFilterJson->json('all_filtered'))->pluck('id')->all());

        $this->actingAs($user)
            ->get(route('issue-follow-up.show', $newerInProgress->id))
            ->assertOk()
            ->assertSee('Temuan Aktif Baru (In Progress)')
            ->assertSee('Perbaikan telah diselesaikan dan diverifikasi.');

        // 5. Buka kembali (Reopen) temuan Closed -> kembali aktif dan muncul lagi di highlight JSON
        $reopenRes = $this->actingAs($user)->postJson(route('issue-follow-up.reopen', $newerInProgress->id), [
            'status' => 'In Progress',
            'note' => 'Perlu pengecekan ulang pasca hujan.',
        ]);

        $reopenRes->assertOk()
            ->assertJsonPath('issue.status', 'In Progress');

        $highlightIdsAfterReopen = collect($reopenRes->json('latest_highlights'))->pluck('id')->all();
        $this->assertContains($newerInProgress->id, $highlightIdsAfterReopen);
    }

    public function test_hsse_division_validation_classification_and_filtering_for_h_s_e_and_legacy_records(): void
    {
        $user = $this->createAuthUser('admin');

        // 1. Validasi wajib hsse_division (harus H, S, atau E)
        $this->actingAs($user)
            ->postJson(route('issue-follow-up.store'), [
                'reported_date' => Carbon::today()->toDateString(),
                'title' => 'Temuan Tanpa Divisi',
                'category' => 'Safety Inspection',
                'unit' => 'PMU',
                'description' => 'Deskripsi temuan.',
                'pic' => 'PIC Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hsse_division']);

        $this->actingAs($user)
            ->postJson(route('issue-follow-up.store'), [
                'reported_date' => Carbon::today()->toDateString(),
                'hsse_division' => 'X',
                'title' => 'Temuan Divisi Invalid',
                'category' => 'Safety Inspection',
                'unit' => 'PMU',
                'description' => 'Deskripsi temuan.',
                'pic' => 'PIC Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hsse_division']);

        // 2. Buat temuan untuk masing-masing divisi H, S_SOCIAL, S_SECURITY, E serta 1 data lama dengan 'S' dan 1 tanpa divisi (null)
        $issueH = IssueFollowUp::create([
            'title' => 'Temuan Kesehatan Ventilasi Ruang Kerja',
            'hsse_division' => 'H',
            'category' => 'Safety Inspection',
            'unit' => 'PMU',
            'reported_date' => Carbon::today()->toDateString(),
            'description' => 'Sirkulasi udara ruang arsip kurang memadai.',
            'pic' => 'Tim Health',
            'status' => 'Open',
        ]);

        $issueSocial = IssueFollowUp::create([
            'title' => 'Temuan Hubungan Masyarakat dan Sosial',
            'hsse_division' => 'S_SOCIAL',
            'category' => 'Housekeeping & 5R',
            'unit' => 'Dieng Unit 1',
            'reported_date' => Carbon::today()->toDateString(),
            'description' => 'Sosialisasi batas aman perimeter warga sekitar.',
            'pic' => 'Tim Sosial',
            'status' => 'In Progress',
        ]);

        $issueSecurity = IssueFollowUp::create([
            'title' => 'Temuan Akses Gerbang Portal Pos Keamanan',
            'hsse_division' => 'S_SECURITY',
            'category' => 'Keamanan (Security)',
            'unit' => 'Dieng Unit 2',
            'reported_date' => Carbon::today()->toDateString(),
            'description' => 'Sensor portal otomatis pos jaga 1 macet.',
            'pic' => 'Tim Scurity',
            'status' => 'Open',
        ]);

        $issueE = IssueFollowUp::create([
            'title' => 'Temuan Lingkungan Drainase Limbah',
            'hsse_division' => 'E',
            'category' => 'Lingkungan & Limbah B3',
            'unit' => 'Utility',
            'reported_date' => Carbon::today()->toDateString(),
            'description' => 'Endapan lumpur pada bak kontrol drainase.',
            'pic' => 'Tim Environment',
            'status' => 'Open',
        ]);

        $legacyS = IssueFollowUp::create([
            'title' => 'Temuan Warisan dengan Kode S Tunggal',
            'hsse_division' => 'S',
            'category' => 'Safety Inspection',
            'unit' => 'PMU',
            'reported_date' => Carbon::today()->toDateString(),
            'description' => 'Data lama sebelum pemisahan Sosial dan Scurity.',
            'pic' => 'Petugas Lama',
            'status' => 'In Progress',
        ]);

        $legacyUnclassified = IssueFollowUp::create([
            'title' => 'Temuan Historis Lama Tanpa Divisi',
            'hsse_division' => null,
            'category' => 'Lainnya',
            'unit' => 'PMU',
            'reported_date' => Carbon::today()->subDays(10)->toDateString(),
            'description' => 'Data lama sebelum klasifikasi divisi.',
            'pic' => 'PIC Lama',
            'status' => 'Open',
        ]);

        // 3. Filter per Divisi H, S_SOCIAL, S_SECURITY, E dan UNCLASSIFIED
        $resH = $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['hsse_division' => 'H']))
            ->assertOk();
        $idsH = collect($resH->json('all_filtered'))->pluck('id')->all();
        $this->assertContains($issueH->id, $idsH);
        $this->assertNotContains($issueSocial->id, $idsH);
        $this->assertNotContains($issueSecurity->id, $idsH);
        $this->assertNotContains($issueE->id, $idsH);

        // Filter S_SOCIAL harus mengambil $issueSocial dan fallback $legacyS, tapi TIDAK $issueSecurity
        $resSocial = $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['hsse_division' => 'S_SOCIAL']))
            ->assertOk();
        $idsSocial = collect($resSocial->json('all_filtered'))->pluck('id')->all();
        $this->assertContains($issueSocial->id, $idsSocial);
        $this->assertContains($legacyS->id, $idsSocial);
        $this->assertNotContains($issueSecurity->id, $idsSocial);
        $this->assertNotContains($issueH->id, $idsSocial);

        // Filter S_SECURITY harus mengambil $issueSecurity saja, TIDAK $issueSocial atau $legacyS
        $resSecurity = $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['hsse_division' => 'S_SECURITY']))
            ->assertOk();
        $idsSecurity = collect($resSecurity->json('all_filtered'))->pluck('id')->all();
        $this->assertContains($issueSecurity->id, $idsSecurity);
        $this->assertNotContains($issueSocial->id, $idsSecurity);
        $this->assertNotContains($legacyS->id, $idsSecurity);

        $resE = $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['hsse_division' => 'E']))
            ->assertOk();
        $idsE = collect($resE->json('all_filtered'))->pluck('id')->all();
        $this->assertContains($issueE->id, $idsE);
        $this->assertNotContains($issueH->id, $idsE);

        $resUnclassified = $this->actingAs($user)
            ->getJson(route('issue-follow-up.index', ['hsse_division' => 'UNCLASSIFIED']))
            ->assertOk();
        $idsUnclassified = collect($resUnclassified->json('all_filtered'))->pluck('id')->all();
        $this->assertContains($legacyUnclassified->id, $idsUnclassified);
        $this->assertNotContains($issueH->id, $idsUnclassified);

        // 4. Pastikan halaman HTML merender 3 kartu ringkasan atas dan 4 grafik ring fungsi safeguard dengan ejaan tepat
        $html = $this->actingAs($user)->get(route('issue-follow-up.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="topSummaryCards"', $html);
        $this->assertStringContainsString('id="safeguardRingsRow"', $html);
        $this->assertStringContainsString('Health &amp; Safety', $html);
        $this->assertStringContainsString('Security', $html);
        $this->assertStringContainsString('Environment', $html);
        $this->assertStringContainsString('Social', $html);
    }

    public function test_issue_documentation_upload_download_and_destroy_flow(): void
    {
        Storage::fake('public');
        $user = $this->createAuthUser('admin');

        $issue = IssueFollowUp::create([
            'title' => 'Temuan Kabel Grounding Lepas',
            'hsse_division' => 'H',
            'category' => 'Safety Inspection',
            'unit' => 'PMU',
            'reported_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(3)->toDateString(),
            'description' => 'Kabel grounding pada panel distribusi longgar.',
            'pic' => 'Teknisi Listrik',
            'status' => 'Open',
        ]);

        $fakeFile = UploadedFile::fake()->create('grounding_proof.jpg', 250, 'image/jpeg');

        // 1. Upload documentation
        $uploadRes = $this->actingAs($user)->post(route('issue-follow-up.documentation.store', $issue->id), [
            'file' => $fakeFile,
            'caption' => 'Foto kabel grounding yang longgar',
        ]);

        $uploadRes->assertSessionHas('success');

        $doc = IssueDocumentation::where('issue_follow_up_id', $issue->id)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('grounding_proof.jpg', $doc->original_name);
        $this->assertTrue($doc->is_image);
        Storage::disk('public')->assertExists($doc->file_path);

        // 2. View documentation inline
        $viewRes = $this->actingAs($user)->get(route('issue-follow-up.documentation.view', $doc->id));
        $viewRes->assertOk();

        // 3. Download documentation
        $dlRes = $this->actingAs($user)->get(route('issue-follow-up.documentation.download', $doc->id));
        $dlRes->assertOk();

        // 4. Show page displays documentation
        $showRes = $this->actingAs($user)->get(route('issue-follow-up.show', $issue->id));
        $showRes->assertOk();
        $showRes->assertSee('grounding_proof.jpg');

        // 5. Delete documentation
        $delRes = $this->actingAs($user)->delete(route('issue-follow-up.documentation.destroy', $doc->id));
        $delRes->assertSessionHas('success');
        $this->assertDatabaseMissing('issue_documentations', ['id' => $doc->id]);
        Storage::disk('public')->assertMissing($doc->file_path);
    }

    public function test_finding_sources_api_crud_flow(): void
    {
        $user = $this->createAuthUser('admin');

        // 1. Get initial sources
        $getRes = $this->actingAs($user)->getJson(route('issue-follow-up.sources.index'));
        $getRes->assertOk();
        $getRes->assertJsonPath('success', true);

        // 2. Store new source
        $postRes = $this->actingAs($user)->postJson(route('issue-follow-up.sources.store'), [
            'name' => 'Audit Keselamatan Khusus 2026',
            'description' => 'Audit berkala tim HSSE Korporat',
        ]);
        $postRes->assertStatus(201);
        $postRes->assertJsonPath('success', true);
        $this->assertDatabaseHas('finding_sources', [
            'name' => 'Audit Keselamatan Khusus 2026',
        ]);

        // 3. Delete source
        $delRes = $this->actingAs($user)->deleteJson(route('issue-follow-up.sources.destroy', 'Audit Keselamatan Khusus 2026'));
        $delRes->assertOk();
        $delRes->assertJsonPath('success', true);
        $this->assertDatabaseMissing('finding_sources', [
            'name' => 'Audit Keselamatan Khusus 2026',
        ]);
    }
}
