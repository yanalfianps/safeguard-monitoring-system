<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IssueFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DecoupleIncidentIssueTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdmin(): User
    {
        return User::where('role', User::ROLE_ADMIN_MANAGEMENT)->first()
            ?: User::where('role', 'Admin')->first()
            ?: User::factory()->create([
                'role' => User::ROLE_ADMIN_MANAGEMENT,
                'status' => 'Aktif',
            ]);
    }

    public function test_incident_module_operates_independently(): void
    {
        $admin = $this->getAdmin();

        // 1. Index
        $response = $this->actingAs($admin)->get('/incident');
        $response->assertStatus(200);

        // 2. Store Incident
        $postData = [
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['First Aid Case'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'tanggal_waktu' => '2026-11-17 09:00:00',
            'lokasi_kejadian' => 'Lokasi Uji Independen HTTP',
            'komponen_terkait' => 'Pipa Geothermal',
            'deskripsi_kejadian' => 'Uji CRUD HTTP Incident Mandiri',
            'status_laporan' => 'Closed',
        ];

        $postResponse = $this->actingAs($admin)->post('/incident', $postData);
        $this->assertTrue(in_array($postResponse->status(), [200, 302]));

        $incident = Incident::where('lokasi_kejadian', 'Lokasi Uji Independen HTTP')->first();
        $this->assertNotNull($incident);

        // 3. Update Incident
        $updateData = [
            'klasifikasi_area' => 'Kecelakaan Panas Bumi',
            'kategori_umum' => ['Property Damage'],
            'kategori_ebtke' => ['Kecelakaan Ringan'],
            'tanggal_waktu' => '2026-11-17 09:00:00',
            'lokasi_kejadian' => 'Lokasi Uji Independen HTTP Terupdate',
            'komponen_terkait' => 'Pipa Geothermal Unit 2',
            'deskripsi_kejadian' => 'Deskripsi update',
            'status_laporan' => 'Open',
        ];

        $putResponse = $this->actingAs($admin)->put('/incident/' . $incident->id, $updateData);
        $this->assertTrue(in_array($putResponse->status(), [200, 302]));

        $incident->refresh();
        $this->assertSame('Lokasi Uji Independen HTTP Terupdate', $incident->lokasi_kejadian);

        // 4. Delete Incident
        $deleteResponse = $this->actingAs($admin)->delete('/incident/' . $incident->id);
        $this->assertTrue(in_array($deleteResponse->status(), [200, 302]));
        $this->assertNull(Incident::find($incident->id));
    }

    public function test_issue_follow_up_module_operates_independently_without_incident_id(): void
    {
        $admin = $this->getAdmin();

        // 1. Index
        $response = $this->actingAs($admin)->get('/issue-follow-up');
        $response->assertStatus(200);

        // 2. Store Issue without any incident_id
        $postData = [
            'hsse_division' => 'H',
            'reported_date' => '2026-11-17',
            'title' => 'Temuan HTTP Mandiri Tanpa Incident',
            'category' => 'Audit GIMS',
            'unit' => 'PMU',
            'location' => 'Wellpad 28',
            'description' => 'Uji mandiri modul issue follow up HTTP',
            'action_plan' => 'Lakukan perbaikan',
            'pic' => 'Tim HSSE',
            'priority' => 'Tinggi',
            'status' => 'Open',
        ];

        $postResponse = $this->actingAs($admin)->post('/issue-follow-up', $postData);
        $this->assertTrue(in_array($postResponse->status(), [200, 302]));

        $issue = IssueFollowUp::where('title', 'Temuan HTTP Mandiri Tanpa Incident')->first();
        $this->assertNotNull($issue);
        $this->assertNull($issue->incident_id);

        // 3. Detail
        $showResponse = $this->actingAs($admin)->get('/issue-follow-up/' . $issue->id);
        $showResponse->assertStatus(200);

        // 4. Update Issue
        $updateData = [
            'hsse_division' => 'H',
            'reported_date' => '2026-11-17',
            'title' => 'Temuan HTTP Mandiri Terupdate',
            'category' => 'Audit GIMS',
            'unit' => 'PMU',
            'location' => 'Wellpad 28',
            'description' => 'Deskripsi update mandiri',
            'action_plan' => 'Lakukan tindakan lanjutan',
            'pic' => 'Tim HSSE',
            'priority' => 'Kritis',
            'status' => 'In Progress',
        ];

        $putResponse = $this->actingAs($admin)->put('/issue-follow-up/' . $issue->id, $updateData);
        $this->assertTrue(in_array($putResponse->status(), [200, 302]));

        $issue->refresh();
        $this->assertSame('Temuan HTTP Mandiri Terupdate', $issue->title);
        $this->assertSame('In Progress', $issue->status);

        // 5. Delete Issue
        $deleteResponse = $this->actingAs($admin)->delete('/issue-follow-up/' . $issue->id);
        $this->assertTrue(in_array($deleteResponse->status(), [200, 302]));
        $this->assertNull(IssueFollowUp::find($issue->id));
    }
}
