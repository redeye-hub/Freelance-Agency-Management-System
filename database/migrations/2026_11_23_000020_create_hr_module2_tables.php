<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['annual', 'sick', 'unpaid', 'other'])->default('annual');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date', 'end_date']);
        });

        // Remembers the availability a freelancer had before leave started, so it can be restored.
        Schema::table('freelancer_profiles', function (Blueprint $table) {
            $table->string('availability_before_leave')->nullable();
        });

        // A contract is generated from a project assignment. Rate, commission and hours are
        // copied (snapshotted) so later edits to the assignment never change a signed contract.
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->nullable()->unique();
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->unsignedSmallInteger('weekly_hours');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('terms')->nullable();
            $table->enum('status', ['sent', 'accepted', 'declined'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('response_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('quality');
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('timeliness');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'freelancer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('contracts');
        Schema::table('freelancer_profiles', function (Blueprint $table) {
            $table->dropColumn('availability_before_leave');
        });
        Schema::dropIfExists('leave_requests');
    }
};
