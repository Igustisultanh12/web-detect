<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Directory of abuse & regulatory providers
        Schema::create('takedown_providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('type'); // Registrar, Hosting, VPS, CDN, DNS, Search Engine, Social Media, Marketplace, Content Platform, Email Provider, Abuse Contact, CERT/CSIRT, Regulator, Law Enforcement, Other
            $table->string('website')->nullable();
            $table->string('abuse_email')->nullable();
            $table->string('abuse_url')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->json('report_types')->nullable(); // Supported report types (phishing, malware, hoax, copyright, financial fraud)
            $table->text('requirements')->nullable(); // Required evidence, documents, identity
            $table->unsignedInteger('sla_hours')->default(72);
            $table->string('integration_status')->default('MANUAL'); // MANUAL, API_AVAILABLE, API_ACTIVE
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Takedown Cases
        Schema::create('takedown_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('case_number')->unique(); // e.g. TKD-2026-000001
            $table->foreignId('investigation_id')->nullable()->constrained('investigations')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('takedown_providers')->nullOnDelete();
            $table->string('target_domain')->index();
            $table->text('target_url');
            $table->string('target_ip')->nullable();
            $table->string('category')->default('Konten Ilegal / Hoax'); // Phishing, Hoax / Disinformasi, Penipuan Finansial, Judi Online, Malware Distribution, Pencemaran Nama Baik / Ilegal, Hak Cipta, Lainnya
            $table->text('allegation_summary'); // Uraian dugaan pelanggaran
            $table->text('legal_or_policy_basis')->nullable(); // Dasar hukum / kebijakan (e.g. UU ITE Pasal 27/28, ToS Provider)
            $table->text('evidence_summary')->nullable();
            $table->json('selected_evidence_ids')->nullable(); // Array of evidence IDs included
            $table->string('provider_type')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('provider_contact')->nullable();
            $table->string('external_reference_number')->nullable(); // Ticket ID from provider
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('last_follow_up_at')->nullable();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, READY_TO_SUBMIT, SUBMITTED, ACKNOWLEDGED, UNDER_REVIEW, ADDITIONAL_INFORMATION_REQUESTED, ACTION_TAKEN, REJECTED, ESCALATED, CLOSED, NO_RESPONSE
            $table->string('priority')->default('MEDIUM'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 3. Follow-up Communications & Ticket Timeline
        Schema::create('takedown_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('takedown_case_id')->constrained('takedown_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('channel')->default('EMAIL'); // EMAIL, PORTAL, PHONE, WHATSAPP, API
            $table->string('direction')->default('OUTBOUND'); // OUTBOUND, INBOUND
            $table->string('ticket_number')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('provider_status')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_sha256')->nullable();
            $table->dateTime('follow_up_date')->nullable();
            $table->string('next_action')->nullable();
            $table->timestamps();
        });

        // 4. Defensive Technical Actions (Internal Infrastructure Only)
        Schema::create('defensive_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('action_code')->unique(); // e.g. DEF-2026-000001
            $table->foreignId('investigation_id')->nullable()->constrained('investigations')->nullOnDelete();
            $table->foreignId('takedown_case_id')->nullable()->constrained('takedown_cases')->nullOnDelete();
            $table->string('rule_type'); // INTERNAL_BLOCKLIST, IOC_LIST, FIREWALL_RULE, WAF_RULE, DNS_SINKHOLE, EMAIL_FILTER, PROXY_BLOCK, SIEM_RULE, INCIDENT_CHECKLIST
            $table->string('target_type'); // DOMAIN, IP, URL, SUBNET, HASH
            $table->string('target_value');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('scope')->default('INTERNAL_NETWORK'); // INTERNAL_NETWORK, CORPORATE_FIREWALL, LOCAL_DNS_RESOLVER, INTERNAL_MAIL_GATEWAY
            $table->longText('rule_payload')->nullable(); // Actual generated rule config (iptables, modsec, rpz, sigma, etc.)
            $table->string('status')->default('DRAFT'); // DRAFT, PENDING_REVIEW, PENDING_APPROVAL, APPROVED, REJECTED, DEPLOYED_INTERNALLY, REVOKED
            $table->foreignId('recommended_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('deployed_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        // 5. Incident Response Checklist
        Schema::create('incident_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('takedown_case_id')->nullable()->constrained('takedown_cases')->cascadeOnDelete();
            $table->foreignId('defensive_action_id')->nullable()->constrained('defensive_actions')->cascadeOnDelete();
            $table->unsignedInteger('step_number')->default(1);
            $table->string('phase')->default('IDENTIFICATION'); // IDENTIFICATION, CONTAINMENT, ERADICATION, RECOVERY, LESSONS_LEARNED
            $table->string('task_name');
            $table->text('instructions')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_checklists');
        Schema::dropIfExists('defensive_actions');
        Schema::dropIfExists('takedown_follow_ups');
        Schema::dropIfExists('takedown_cases');
        Schema::dropIfExists('takedown_providers');
    }
};
