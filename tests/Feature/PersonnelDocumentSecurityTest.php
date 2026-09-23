<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonnelDocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test unauthenticated access is rejected.
     */
    public function test_unauthenticated_user_cannot_view_document(): void
    {
        $response = $this->getJson('/api/v1/documents/' . Str::uuid() . '/view');
        $response->assertStatus(401);
    }

    /**
     * Test viewer role without user.document.view permission gets 403 Forbidden.
     */
    public function test_viewer_without_permission_receives_403_forbidden_on_view(): void
    {
        $viewer = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Tamu Pengamat',
            'email' => 'viewer.test@webguard.mil.id',
            'password' => bcrypt('StrongPass123!'),
            'status' => 'ACTIVE',
        ]);
        $viewer->assignRole('viewer');

        $doc = UserDocument::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'document_type' => 'KTP',
            'file_path' => 'documents/ktp_test.pdf',
            'original_filename' => 'ktp_test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'sha256' => hash('sha256', 'dummy-ktp-content'),
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/documents/{$doc->uuid}/view");

        $response->assertStatus(403);
    }

    /**
     * Test viewer without permission receives 403 on download.
     */
    public function test_viewer_without_permission_receives_403_forbidden_on_download(): void
    {
        $viewer = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Tamu Pengamat',
            'email' => 'viewer.download.test@webguard.mil.id',
            'password' => bcrypt('StrongPass123!'),
            'status' => 'ACTIVE',
        ]);
        $viewer->assignRole('viewer');

        $doc = UserDocument::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $viewer->id,
            'document_type' => 'KTA',
            'file_path' => 'documents/kta_test.pdf',
            'original_filename' => 'kta_test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'sha256' => hash('sha256', 'dummy-kta-content'),
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/documents/{$doc->uuid}/download");

        $response->assertStatus(403);
    }

    /**
     * Test document upload stores file in private disk with SHA-256 hash.
     */
    public function test_admin_can_upload_personnel_document_with_sha256(): void
    {
        Storage::fake('local');

        $admin = User::where('email', 'superadmin@webguard.mil.id')->first();
        if (!$admin) {
            $admin = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => 'Super Admin',
                'email' => 'admin.test@webguard.mil.id',
                'password' => bcrypt('StrongPass123!'),
                'status' => 'ACTIVE',
            ]);
            $admin->assignRole('super_admin');
        }

        $fakeFile = UploadedFile::fake()->create('ktp_rahasia.png', 200, 'image/png');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/users/{$admin->uuid}/documents", [
                'document' => $fakeFile,
                'document_type' => 'KTP',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_documents', [
            'user_id' => $admin->id,
            'document_type' => 'KTP',
            'original_filename' => 'ktp_rahasia.png',
        ]);
    }
}
