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
}
