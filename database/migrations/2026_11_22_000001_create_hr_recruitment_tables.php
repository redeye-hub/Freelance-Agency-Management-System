<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('headline')->nullable();
            $table->string('skills')->nullable();          // comma separated, e.g. "Laravel, Vue"
            $table->decimal('expected_rate', 8, 2)->nullable();
            $table->string('portfolio_url')->nullable();
            $table->text('cover_note')->nullable();
            $table->enum('stage', ['applied', 'screening', 'interview', 'approved', 'rejected'])->default('applied');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('interview_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // set on approval
            $table->timestamps();
        });

        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['id', 'portfolio', 'nda', 'other'])->default('other');
            $table->string('original_name');
            $table->string('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hr_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('offboarding_reason')->nullable();
            $table->timestamp('offboarded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['offboarding_reason', 'offboarded_at']);
        });
        Schema::dropIfExists('hr_audit_logs');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidates');
    }
};
