<?php

namespace Tests\Feature;

use App\Models\DefensiveAction;
use App\Models\User;
use App\Services\Takedown\DefensiveActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DefensiveActionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $investigator;
    protected User $admin;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->investigator = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Letda Chb Bagus',
            'email' => 'bagus@webguard.mil.id',
            'password' => bcrypt('Investigator123!'),
            'status' => 'ACTIVE',
        ]);
        $this->investigator->assignRole('investigator');

        $this->admin = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Kapten Inf Hendra',
            'email' => 'hendra@webguard.mil.id',
            'password' => bcrypt('Admin123!'),
            'status' => 'ACTIVE',
        ]);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Kolonel Inf Suryo',
            'email' => 'suryo@webguard.mil.id',
            'password' => bcrypt('SuperAdmin123!'),
            'status' => 'ACTIVE',
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    /**
     * Test investigator can create defensive rule recommendation with automatic payload & SOP checklist.
     */
    public function test_investigator_can_create_defensive_rule_recommendation(): void
    {
        $response = $this->actingAs($this->investigator, 'sanctum')
            ->postJson('/api/v1/defensive-actions', [
                'rule_type' => 'FIREWALL_RULE',
                'target_type' => 'IP',
                'target_value' => '198.51.100.99',
                'title' => 'Blokir IP Host Phishing',
                'scope' => 'CORPORATE_FIREWALL',
                'description' => 'Blokir koneksi keluar dari intranet menuju server penyerang.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $actionCode = $response->json('action.action_code');
        $this->assertMatchesRegularExpression('/^DEF-\d{4}-\d{6}$/', $actionCode);

        $action = DefensiveAction::where('action_code', $actionCode)->first();
        $this->assertNotNull($action);
        $this->assertEquals('PENDING_REVIEW', $action->status);
        $this->assertStringContainsString('iptables', $action->rule_payload);
        $this->assertCount(8, $action->checklists);
    }

    /**
     * Test multi-tier governance: Investigator -> Admin (Review) -> Super Admin (Approve).
     */
    public function test_defensive_action_approval_workflow(): void
    {
        $action = DefensiveAction::create([
            'uuid' => (string) Str::uuid(),
            'action_code' => 'DEF-2026-000201',
            'rule_type' => 'DNS_SINKHOLE',
            'target_type' => 'DOMAIN',
            'target_value' => 'fake-portal-login.xyz',
            'title' => 'Sinkhole Domain Penipuan',
            'scope' => 'LOCAL_DNS_RESOLVER',
            'status' => 'DRAFT',
            'recommended_by' => $this->investigator->id,
        ]);

        // 1. Investigator cannot approve
        $failApprove = $this->actingAs($this->investigator, 'sanctum')
            ->postJson("/api/v1/defensive-actions/{$action->id}/approve");
        $failApprove->assertStatus(403);

        // 2. Admin reviews the rule
        $reviewRes = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/defensive-actions/{$action->id}/review");
        $reviewRes->assertStatus(200);

        $action->refresh();
        $this->assertEquals('PENDING_APPROVAL', $action->status);
        $this->assertEquals($this->admin->id, $action->reviewed_by);

        // 3. Super Admin approves the rule
        $approveRes = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v1/defensive-actions/{$action->id}/approve");
        $approveRes->assertStatus(200);

        $action->refresh();
        $this->assertEquals('APPROVED', $action->status);
        $this->assertEquals($this->superAdmin->id, $action->approved_by);
        $this->assertNotNull($action->approved_at);

        // 4. Mark deployed
        $deployRes = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/defensive-actions/{$action->id}/deploy");
        $deployRes->assertStatus(200);

        $action->refresh();
        $this->assertEquals('DEPLOYED_INTERNALLY', $action->status);
        $this->assertNotNull($action->deployed_at);
    }

    /**
     * Test rule payload generator outputs strictly defensive configurations.
     */
    public function test_defensive_rule_payload_generator(): void
    {
        $service = app(DefensiveActionService::class);

        // WAF Rule
        $wafRule = $service->generateRulePayload('WAF_RULE', 'DOMAIN', 'malicious-exploit-server.com');
        $this->assertStringContainsString('SecRule', $wafRule);
        $this->assertStringContainsString('Cloudflare WAF Custom Rule', $wafRule);

        // DNS Sinkhole
        $sinkhole = $service->generateRulePayload('DNS_SINKHOLE', 'DOMAIN', 'phishing-credential-bank.com');
        $this->assertStringContainsString('BIND 9 Response Policy Zone', $sinkhole);
        $this->assertStringContainsString('127.0.0.1', $sinkhole);

        // SIEM Sigma Rule
        $sigma = $service->generateRulePayload('SIEM_RULE', 'DOMAIN', 'c2-control-node.org');
        $this->assertStringContainsString('title: WebGuard Internal Detection', $sigma);
        $this->assertStringContainsString('c2-control-node.org', $sigma);
        $this->assertStringContainsString('level: high', $sigma);

        // Guarantee strictly defensive (no destructive keywords)
        $this->assertStringNotContainsString('exploit', strtolower($sinkhole));
        $this->assertStringNotContainsString('flood', strtolower($sinkhole));
    }
}
