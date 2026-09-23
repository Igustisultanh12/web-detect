<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('investigation_code')->unique()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('target_url');
            $table->string('target_domain')->index();
            $table->string('category')->default('Suspicious Domain');
            $table->text('reason')->nullable();
            $table->string('priority')->default('MEDIUM'); // LOW, MEDIUM, HIGH, CRITICAL
            $table->json('tags')->nullable();
            $table->string('status')->default('QUEUED')->index(); // QUEUED, ANALYZING, COMPLETED, FAILED, PARTIAL_RESULT
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('domain_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('domain');
            $table->string('tld')->nullable();
            $table->string('registrar')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('domain_status')->nullable();
            $table->json('nameservers')->nullable();
            $table->string('dnssec_status')->nullable();
            $table->json('rdap_data')->nullable();
            $table->timestamps();
        });

        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('record_type', 16)->index();
            $table->string('host');
            $table->text('target');
            $table->integer('ttl')->nullable();
            $table->integer('priority')->nullable();
            $table->text('raw_entry')->nullable();
            $table->timestamps();
        });

        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('ip_address', 45)->index();
            $table->string('ip_version', 8)->default('v4');
            $table->boolean('is_cdn_or_proxy')->default(false);
            $table->string('cdn_provider')->nullable();
            $table->string('reverse_dns')->nullable();
            $table->timestamps();
        });

        Schema::create('asn_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('ip_address', 45)->index();
            $table->string('asn', 32)->nullable();
            $table->string('asn_org')->nullable();
            $table->string('bgp_prefix')->nullable();
            $table->string('registry')->nullable();
            $table->timestamps();
        });

        Schema::create('hosting_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('ip_address', 45)->index();
            $table->string('isp')->nullable();
            $table->string('organization')->nullable();
            $table->string('hosting_type')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_datacenter')->default(false);
            $table->timestamps();
        });

        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('subject_cn')->nullable();
            $table->string('subject_org')->nullable();
            $table->string('issuer_cn')->nullable();
            $table->string('issuer_org')->nullable();
            $table->json('san_list')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('tls_version')->nullable();
            $table->string('cipher')->nullable();
            $table->string('signature_algorithm')->nullable();
            $table->integer('public_key_bits')->nullable();
            $table->json('cert_chain')->nullable();
            $table->string('ct_status')->nullable();
            $table->timestamps();
        });

        Schema::create('http_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->integer('http_status')->nullable();
            $table->boolean('https_available')->default(true);
            $table->text('final_url')->nullable();
            $table->json('redirect_chain')->nullable();
            $table->string('server_header')->nullable();
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('content_length')->nullable();
            $table->string('compression')->nullable();
            $table->string('hsts_header')->nullable();
            $table->text('csp_header')->nullable();
            $table->string('x_frame_options')->nullable();
            $table->string('x_content_type_options')->nullable();
            $table->string('referrer_policy')->nullable();
            $table->text('permissions_policy')->nullable();
            $table->json('cookies_data')->nullable();
            $table->integer('security_score')->nullable();
            $table->json('security_notes')->nullable();
            $table->json('raw_headers')->nullable();
            $table->timestamps();
        });

        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('category');
            $table->string('name');
            $table->string('version')->nullable();
            $table->integer('confidence')->default(100);
            $table->string('matched_pattern')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('subdomains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('subdomain')->index();
            $table->string('source')->default('Certificate Transparency');
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reputation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('provider_name');
            $table->string('status')->default('CLEAN'); // CLEAN, SUSPICIOUS, MALICIOUS, UNKNOWN
            $table->string('threat_type')->nullable();
            $table->integer('score')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('screenshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('file_path');
            $table->text('original_url');
            $table->string('sha256', 64)->index();
            $table->integer('width')->default(1280);
            $table->integer('height')->default(800);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screenshots');
        Schema::dropIfExists('reputation_results');
        Schema::dropIfExists('subdomains');
        Schema::dropIfExists('technologies');
        Schema::dropIfExists('http_results');
        Schema::dropIfExists('ssl_certificates');
        Schema::dropIfExists('hosting_records');
        Schema::dropIfExists('asn_records');
        Schema::dropIfExists('ip_addresses');
        Schema::dropIfExists('dns_records');
        Schema::dropIfExists('domain_records');
        Schema::dropIfExists('investigations');
    }
};
