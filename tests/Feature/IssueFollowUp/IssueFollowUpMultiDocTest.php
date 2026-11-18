<?php

namespace Tests\Feature\IssueFollowUp;

use App\Models\IssueDocumentation;
use App\Models\IssueFollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueFollowUpMultiDocTest extends TestCase
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

    public function test_multi_document_lifecycle_flow(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->getAdmin();

        // 1. Membuat temuan dengan 4 dokumen
        $doc1 = UploadedFile::fake()->create('Halaman Login.jpg', 30, 'image/jpeg');
        $doc2 = UploadedFile::fake()->create('Halaman Pemesanan.jpg', 38, 'image/jpeg');
        $doc3 = UploadedFile::fake()->create('Halaman Pengaturan.pdf', 35, 'application/pdf');
        $doc4 = UploadedFile::fake()->create('Halaman Profil.png', 31, 'image/png');

        $createResponse = $this->actingAs($admin)->post('/issue-follow-up', [
            'hsse_division' => 'H',
            'reported_date' => '2026-11-17',
            'title' => 'Uji Temuan Multi-File Dokumentasi',
            'category' => 'Audit GIMS',
            'unit' => 'PMU',
            'location' => 'Area Operasi Dieng',
            'description' => 'Deskripsi temuan awal dengan 4 lampiran',
            'action_plan' => 'Rencana tindakan perbaikan',
            'pic' => 'Tim HSSE',
            'priority' => 'Tinggi',
            'status' => 'Open',
            'documentations' => [$doc1, $doc2, $doc3, $doc4],
        ]);

        $this->assertTrue(in_array($createResponse->status(), [200, 201, 302]));

        $issue = IssueFollowUp::where('title', 'Uji Temuan Multi-File Dokumentasi')->first();
        $this->assertNotNull($issue);

        $this->assertSame(4, $issue->documentations()->count());
        $initialDocs = $issue->documentations()->orderBy('id')->get();
        $this->assertCount(4, $initialDocs);

        // 2. Membuka Edit Temuan dan memastikan 4 dokumen lama muncul
        // A. Via Web View (edit.blade.php)
        $editViewResponse = $this->actingAs($admin)->get('/issue-follow-up/' . $issue->id . '/edit');
        $editViewResponse->assertStatus(200);
        $editViewResponse->assertSee('Dokumen Tersimpan Saat Ini', false);
        $editViewResponse->assertSee('<span id="existingDocsCount">4</span>', false);
        $editViewResponse->assertSee('Halaman Login.jpg');
        $editViewResponse->assertSee('Halaman Pemesanan.jpg');
        $editViewResponse->assertSee('Halaman Pengaturan.pdf');
        $editViewResponse->assertSee('Halaman Profil.png');
        $editViewResponse->assertSee('markDeleteExistingDoc');

        // B. Via AJAX Endpoint (digunakan oleh Modal Edit di index.blade.php)
        $ajaxResponse = $this->actingAs($admin)->getJson('/issue-follow-up/' . $issue->id);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['success' => true]);
        $ajaxDocs = $ajaxResponse->json('documentations');
        $this->assertCount(4, $ajaxDocs);

        // 3. Menghapus 1 dokumen dan menyimpan (Hapus doc: Halaman Pemesanan.jpg)
        $docToDelete = $initialDocs->firstWhere('original_name', 'Halaman Pemesanan.jpg');
        $this->assertNotNull($docToDelete);
        $docToDeleteId = $docToDelete->id;
        $docToDeletePath = $docToDelete->file_path;

        $updateResponse1 = $this->actingAs($admin)->put('/issue-follow-up/' . $issue->id, [
            'hsse_division' => 'H',
            'reported_date' => '2026-11-17',
            'title' => 'Uji Temuan Multi-File Dokumentasi',
            'category' => 'Audit GIMS',
            'unit' => 'PMU',
            'location' => 'Area Operasi Dieng',
            'description' => 'Deskripsi temuan setelah 1 dokumen dihapus',
            'action_plan' => 'Rencana tindakan perbaikan',
            'pic' => 'Tim HSSE',
            'priority' => 'Tinggi',
            'status' => 'Open',
            'deleted_documentations' => [$docToDeleteId],
        ]);

        $this->assertTrue(in_array($updateResponse1->status(), [200, 302]));

        // 4. Memastikan hanya 3 dokumen lama yang tersisa
        $issue->refresh();
        $this->assertSame(3, $issue->documentations()->count());
        $this->assertFalse($issue->documentations()->where('id', $docToDeleteId)->exists());
        $remainingDocNames = $issue->documentations()->pluck('original_name')->all();
        $this->assertContains('Halaman Login.jpg', $remainingDocNames);
        $this->assertNotContains('Halaman Pemesanan.jpg', $remainingDocNames);
        $this->assertContains('Halaman Pengaturan.pdf', $remainingDocNames);
        $this->assertContains('Halaman Profil.png', $remainingDocNames);

        // 5. Menambahkan 2 dokumen baru
        $doc5 = UploadedFile::fake()->create('Dokumen Tambahan 1.docx', 40, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $doc6 = UploadedFile::fake()->create('Dokumen Tambahan 2.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $updateResponse2 = $this->actingAs($admin)->put('/issue-follow-up/' . $issue->id, [
            'hsse_division' => 'H',
            'reported_date' => '2026-11-17',
            'title' => 'Uji Temuan Multi-File Dokumentasi',
            'category' => 'Audit GIMS',
            'unit' => 'PMU',
            'location' => 'Area Operasi Dieng',
            'description' => 'Deskripsi setelah tambah 2 dokumen baru',
            'action_plan' => 'Rencana tindakan perbaikan',
            'pic' => 'Tim HSSE',
            'priority' => 'Tinggi',
            'status' => 'Open',
            'documentations' => [$doc5, $doc6],
        ]);

        $this->assertTrue(in_array($updateResponse2->status(), [200, 302]));

        // 6. Memastikan total menjadi 5 dokumen
        $issue->refresh();
        $this->assertSame(5, $issue->documentations()->count());
        $allNames = $issue->documentations()->pluck('original_name')->all();
        $this->assertContains('Halaman Login.jpg', $allNames);
        $this->assertContains('Halaman Pengaturan.pdf', $allNames);
        $this->assertContains('Halaman Profil.png', $allNames);
        $this->assertContains('Dokumen Tambahan 1.docx', $allNames);
        $this->assertContains('Dokumen Tambahan 2.xlsx', $allNames);

        // 7. Mengedit deskripsi tanpa mengubah dokumen dan memastikan seluruh dokumen tetap ada
        $updateResponse3 = $this->actingAs($admin)->put('/issue-follow-up/' . $issue->id, [
            'hsse_division' => 'H',
            'reported_date' => '2026-11-17',
            'title' => 'Uji Temuan Multi-File Dokumentasi (Deskripsi Diperbarui)',
            'category' => 'Audit GIMS',
            'unit' => 'PMU',
            'location' => 'Area Operasi Dieng',
            'description' => 'Deskripsi baru yang telah diedit tanpa menyentuh dokumen sama sekali.',
            'action_plan' => 'Rencana tindakan perbaikan',
            'pic' => 'Tim HSSE',
            'priority' => 'Tinggi',
            'status' => 'Open',
        ]);

        $this->assertTrue(in_array($updateResponse3->status(), [200, 302]));

        $issue->refresh();
        $this->assertSame('Deskripsi baru yang telah diedit tanpa menyentuh dokumen sama sekali.', $issue->description);
        $this->assertSame(5, $issue->documentations()->count());
        $finalNames = $issue->documentations()->pluck('original_name')->all();
        $this->assertCount(5, $finalNames);
        $this->assertContains('Halaman Login.jpg', $finalNames);
        $this->assertContains('Halaman Pengaturan.pdf', $finalNames);
        $this->assertContains('Halaman Profil.png', $finalNames);
        $this->assertContains('Dokumen Tambahan 1.docx', $finalNames);
        $this->assertContains('Dokumen Tambahan 2.xlsx', $finalNames);
    }
}
