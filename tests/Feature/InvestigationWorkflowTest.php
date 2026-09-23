<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\Investigation;
use App\Models\User;
use App\Services\Evidence\EvidenceManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvestigationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test creating investigation rejects internal/loopback URL.
     */
    public function test_creating_investigation_with_ssrf_url_is_rejected(): void
    {
        $investigator = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Penyidik Siber',
            'email' => 'investigator.test@webguard.mil.id',
            'password' => bcrypt('StrongPass123!'),
            'status' => 'ACTIVE',
        ]);
        $investigator->assignRole('investigator');

        $response = $this->actingAs($investigator, 'sanctum')
            ->postJson('/api/v1/investigations', [
                'target_url' => 'http://127.0.0.1:8080/admin',
                'category' => 'Suspicious Domain',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_url']);
    }

    /**
     * Test creating investigation generates proper case code format (WG-YYYY-XXXXXX).
     */
    public function test_creating_investigation_generates_case_code(): void
    {
        $investigator = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Penyidik Siber 2',
            'email' => 'investigator2.test@webguard.mil.id',
            'password' => bcrypt('StrongPass123!'),
            'status' => 'ACTIVE',
        ]);
        $investigator->assignRole('investigator');

        $year = date('Y');

        $investigation = Investigation::create([
            'user_id' => $investigator->id,
            'target_url' => 'https://example.com',
            'target_domain' => 'example.com',
            'category' => 'Phishing',
            'priority' => 'HIGH',
            'status' => 'QUEUED',
        ]);

        $this->assertNotEmpty($investigation->investigation_code);
        $this->assertStringStartsWith("WG-{$year}-", $investigation->investigation_code);
        $this->assertEquals('QUEUED', $investigation->status);
    }

    /**
     * Test evidence manager creates cryptographic hash and verifies integrity.
     */
    public function test_evidence_manager_creates_sha256_and_verifies_integrity(): void
    {
        $admin = User::first();
        $investigation = Investigation::create([
            'user_id' => $admin->id,
            'target_url' => 'https://example.com',
            'target_domain' => 'example.com',
            'category' => 'Suspicious Domain',
            'status' => 'COMPLETED',
        ]);

        $evidenceManager = app(EvidenceManagerService::class);
        $payload = ['dns_records' => [['type' => 'A', 'ip' => '93.184.216.34']]];

        $evidence = $evidenceManager->record(
            $investigation,
            'DNS_RECORD',
            'SystemDnsProvider',
            $payload
        );

        $this->assertNotNull($evidence->sha256);
        $expectedRaw = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->assertEquals(hash('sha256', $expectedRaw), $evidence->sha256);

        // Verify cryptographic match
        $isValid = $evidenceManager->verifyIntegrity($evidence);
        $this->assertTrue($isValid);
    }

    /**
     * Test screenshot provider capture and evidence controller renderRaw endpoint.
     */
    public function test_screenshot_provider_and_evidence_controller_render_visual(): void
    {
        $admin = User::first();
        $investigation = Investigation::create([
            'user_id' => $admin->id,
            'target_url' => 'https://example.com',
            'target_domain' => 'example.com',
            'category' => 'Suspicious Domain',
            'status' => 'COMPLETED',
        ]);

        $provider = app(\App\Contracts\ScreenshotProviderInterface::class);
        $shot = $provider->capture($investigation->target_url, $investigation->investigation_code);

        $this->assertNotEmpty($shot['file_path']);
        $this->assertNotEmpty($shot['sha256']);

        $screenshot = \App\Models\Screenshot::create([
            'investigation_id' => $investigation->id,
            'file_path' => $shot['file_path'],
            'original_url' => $investigation->target_url,
            'sha256' => $shot['sha256'],
            'width' => $shot['width'],
            'height' => $shot['height'],
            'file_size' => $shot['file_size'],
            'captured_at' => $shot['captured_at'],
        ]);

        // Request visual evidence raw render
        $response = $this->get("/api/v1/evidence/{$screenshot->sha256}");
        $response->assertStatus(200);

        $contentType = $response->headers->get('Content-Type');
        $this->assertTrue(
            str_contains($contentType, 'image/png') || str_contains($contentType, 'image/svg+xml'),
            "Expected image content type but got {$contentType}"
        );
    }
}
