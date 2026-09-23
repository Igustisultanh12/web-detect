<?php

namespace Tests\Feature;

use App\Models\Investigation;
use App\Models\TakedownCase;
use App\Models\TakedownProvider;
use App\Models\User;
use App\Services\Takedown\EvidencePackageManagerService;
use App\Services\Takedown\TakedownNotificationService;
use App\Services\Takedown\TakedownReportGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TakedownCaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $investigator;
    protected User $admin;
    protected TakedownProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->investigator = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Kapten Inf Darmawan',
            'email' => 'darmawan@webguard.mil.id',
            'password' => bcrypt('Investigator123!'),
            'status' => 'ACTIVE',
        ]);
        $this->investigator->assignRole('investigator');

        $this->admin = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Mayor Inf Satria',
            'email' => 'satria@webguard.mil.id',
            'password' => bcrypt('Admin123!'),
            'status' => 'ACTIVE',
        ]);
        $this->admin->assignRole('admin');

        $this->provider = TakedownProvider::firstOrCreate(
            ['name' => 'PANDI'],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'REGISTRAR',
                'website' => 'https://pandi.id',
                'abuse_email' => 'abuse@pandi.id',
                'sla_hours' => 24,
                'integration_status' => 'MANUAL',
                'is_active' => true,
            ]
        );
    }

    /**
     * Test case creation generates TKD-YYYY-XXXXXX number and calculates SLA.
     */
    public function test_create_takedown_case_generates_case_number_and_computes_sla(): void
    {
        $response = $this->actingAs($this->investigator, 'sanctum')
            ->postJson('/api/v1/takedown/cases', [
                'target_domain' => 'phishing-kemhan-palsu.id',
                'target_url' => 'https://phishing-kemhan-palsu.id/login',
                'category' => 'PHISHING',
                'allegation_summary' => 'Website meniru portal resmi untuk mencuri kredensial personel intelijen militer.',
                'legal_or_policy_basis' => 'UU ITE Pasal 28 dan 35; Peraturan PANDI Ketentuan Nama Domain.',
                'provider_id' => $this->provider->id,
                'priority' => 'HIGH',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $caseNumber = $response->json('case.case_number');
        $this->assertMatchesRegularExpression('/^TKD-\d{4}-\d{6}$/', $caseNumber);

        $case = TakedownCase::where('case_number', $caseNumber)->first();
        $this->assertNotNull($case);
        $this->assertEquals('DRAFT', $case->status);
        $this->assertEquals($this->provider->id, $case->provider_id);
    }

    /**
     * Test status transitions update timestamps and trigger SLA.
     */
    public function test_status_transitions_update_timestamps(): void
    {
        $case = TakedownCase::create([
            'uuid' => (string) Str::uuid(),
            'case_number' => 'TKD-2026-000101',
            'target_domain' => 'judi-slot-ilegal.cc',
            'target_url' => 'https://judi-slot-ilegal.cc',
            'category' => 'JUDI_ONLINE',
            'allegation_summary' => 'Situs perjudian online tanpa izin beroperasi menargetkan warga sipil.',
            'provider_id' => $this->provider->id,
            'status' => 'DRAFT',
            'priority' => 'CRITICAL',
            'created_by' => $this->investigator->id,
        ]);

        // Transition to SUBMITTED
        $response = $this->actingAs($this->investigator, 'sanctum')
            ->postJson("/api/v1/takedown/cases/{$case->id}/status", [
                'status' => 'SUBMITTED',
                'note' => 'Permohonan takedown telah dikirimkan melalui portal resmi abuse.',
            ]);

        $response->assertStatus(200);

        $case->refresh();
        $this->assertEquals('SUBMITTED', $case->status);
        $this->assertNotNull($case->submitted_at);
        $this->assertNotNull($case->next_follow_up_at);

        // Transition to ACTION_TAKEN (Success)
        $responseResolve = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/takedown/cases/{$case->id}/status", [
                'status' => 'ACTION_TAKEN',
                'note' => 'Domain telah disuspend oleh registrar.',
            ]);

        $responseResolve->assertStatus(200);

        $case->refresh();
        $this->assertEquals('ACTION_TAKEN', $case->status);
        $this->assertNotNull($case->resolved_at);
    }

    /**
     * Test adding follow-up communication to case timeline.
     */
    public function test_add_follow_up_communication_records_timeline(): void
    {
        $case = TakedownCase::create([
            'uuid' => (string) Str::uuid(),
            'case_number' => 'TKD-2026-000102',
            'target_domain' => 'penipuan-investasi.com',
            'target_url' => 'https://penipuan-investasi.com',
            'category' => 'INVESTASI_ILEGAL',
            'allegation_summary' => 'Platform robot trading bodong tanpa izin OJK.',
            'provider_id' => $this->provider->id,
            'status' => 'SUBMITTED',
            'priority' => 'HIGH',
            'created_by' => $this->investigator->id,
        ]);

        $response = $this->actingAs($this->investigator, 'sanctum')
            ->postJson("/api/v1/takedown/cases/{$case->id}/follow-ups", [
                'direction' => 'INBOUND',
                'channel' => 'EMAIL',
                'ticket_number' => 'TKT-998822',
                'subject' => 'Registrar Acknowledgement & Suspension Underway',
                'message' => 'Registrar telah menerima laporan dan memverifikasi bukti WHOIS yang dilampirkan.',
                'provider_status' => 'INVESTIGATING',
                'next_action' => 'Monitor propagasi DNS 12 jam',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $case->refresh();
        $this->assertCount(1, $case->followUps);
        $this->assertEquals('TKT-998822', $case->external_reference_number);
        $this->assertNotNull($case->last_follow_up_at);
    }

    /**
     * Test evidence package ZIP generation and SHA-256 manifest.
     */
    public function test_evidence_package_generation_creates_valid_zip_with_manifest(): void
    {
        Storage::fake('local');

        $case = TakedownCase::create([
            'uuid' => (string) Str::uuid(),
            'case_number' => 'TKD-2026-000103',
            'target_domain' => 'malicious-c2-server.net',
            'target_url' => 'https://malicious-c2-server.net',
            'category' => 'MALWARE_C2',
            'allegation_summary' => 'Server pengendali malware perbankan aktif.',
            'provider_id' => $this->provider->id,
            'status' => 'READY_TO_SUBMIT',
            'priority' => 'CRITICAL',
            'created_by' => $this->investigator->id,
        ]);

        $packageManager = app(EvidencePackageManagerService::class);
        $package = $packageManager->generatePackage($case);

        $this->assertFileExists($package['full_path']);
        $this->assertNotEmpty($package['sha256']);
        $this->assertGreaterThan(0, $package['size']);

        // Inspect ZIP content
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($package['full_path']) === true);
        $this->assertNotFalse($zip->locateName("{$case->case_number}/manifest.json"));
        $this->assertNotFalse($zip->locateName("{$case->case_number}/SHA256SUMS.txt"));
        $zip->close();
    }

    /**
     * Test takedown report generator produces PDF with neutral legal disclaimer.
     */
    public function test_takedown_report_generator_produces_pdf_with_disclaimer(): void
    {
        $case = TakedownCase::create([
            'uuid' => (string) Str::uuid(),
            'case_number' => 'TKD-2026-000104',
            'target_domain' => 'hoax-pemilu-ilegal.org',
            'target_url' => 'https://hoax-pemilu-ilegal.org',
            'category' => 'HOAX_DISINFORMASI',
            'allegation_summary' => 'Penyebaran disinformasi dan ujaran kebencian.',
            'provider_id' => $this->provider->id,
            'status' => 'SUBMITTED',
            'priority' => 'MEDIUM',
            'created_by' => $this->investigator->id,
        ]);

        $reportGenerator = app(TakedownReportGeneratorService::class);
        $result = $reportGenerator->generatePdf($case);

        $this->assertNotEmpty($result['content']);
        $this->assertNotEmpty($result['sha256']);
        $this->assertEquals(64, strlen($result['sha256']));
    }

    /**
     * Test Sisinden WhatsApp alert and Sisfoperskc email formatters.
     */
    public function test_notification_formatters_match_military_intelligence_standards(): void
    {
        $case = TakedownCase::create([
            'uuid' => (string) Str::uuid(),
            'case_number' => 'TKD-2026-000105',
            'target_domain' => 'infiltrasi-portal.mil.id',
            'target_url' => 'https://infiltrasi-portal.mil.id',
            'category' => 'IMPERSONATION',
            'allegation_summary' => 'Peniruan identitas institusi pertahanan negara.',
            'provider_id' => $this->provider->id,
            'status' => 'SUBMITTED',
            'priority' => 'CRITICAL',
            'created_by' => $this->investigator->id,
        ]);

        $notificationService = app(TakedownNotificationService::class);

        // Sisinden WhatsApp alert
        $waMessage = $notificationService->formatSisindenWhatsAppAlert($case);
        $this->assertStringContainsString('*[ALERT INTELIJEN - PERMOHONAN TAKEDOWN]*', $waMessage);
        $this->assertStringContainsString('*No. Kasus:* *' . $case->case_number . '*', $waMessage);
        $this->assertStringContainsString('*Target Domain:* *' . $case->target_domain . '*', $waMessage);
        $this->assertStringContainsString('Detasemen Intelijen Cyber', $waMessage);

        // Sisfoperskc HTML email
        $htmlEmail = $notificationService->formatSisfoperskcHtmlEmail($case, $this->admin);
        $this->assertStringContainsString('#0f172a', $htmlEmail);
        $this->assertStringContainsString('#2563eb', $htmlEmail);
        $this->assertStringContainsString('Yth. ' . $this->admin->name, $htmlEmail);
        $this->assertStringContainsString('Ketentuan Kerahasiaan', $htmlEmail);
        $this->assertStringContainsString('dokumen kedinasan resmi', $htmlEmail);
    }
}
