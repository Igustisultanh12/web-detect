<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('evidence_code')->index(); // EV-000001
            $table->string('type', 64)->index(); // Screenshot, DNS Result, IP Information, etc.
            $table->string('source');
            $table->longText('raw_data');
            $table->json('parsed_data')->nullable();
            $table->string('sha256', 64)->index();
            $table->timestamp('collected_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('evidence_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidences')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('investigation_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('action');
            $table->string('event_type')->default('INFO'); // INFO, SUCCESS, WARNING, ERROR
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('investigation_id')->constrained('investigations')->cascadeOnDelete();
            $table->string('report_number')->unique()->index();
            $table->string('title');
            $table->string('format', 16)->default('PDF'); // PDF, CSV, JSON, XLSX
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('sha256', 64)->index();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('investigation_timeline');
        Schema::dropIfExists('evidence_versions');
        Schema::dropIfExists('evidences');
    }
};
