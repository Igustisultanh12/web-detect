<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('service_type', 32)->index(); // dns, whois, ip_intel, reputation, screenshot, whatsapp
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('api_endpoint')->nullable();
            $table->text('api_key')->nullable(); // encrypted
            $table->text('secret_key')->nullable(); // encrypted
            $table->text('extra_config')->nullable(); // encrypted json
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('result', 16)->default('SUCCESS');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64)->index();
            $table->string('severity', 16)->default('INFO'); // INFO, WARNING, HIGH, CRITICAL
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->longText('value')->nullable();
            $table->string('group')->default('general');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('api_providers');
    }
};
