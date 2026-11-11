<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->enum('availability', ['available', 'busy', 'unavailable'])->default('available');
            $table->timestamps();
        });

        Schema::create('freelancer_profile_skill', function (Blueprint $table) {
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['freelancer_profile_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profile_skill');
        Schema::dropIfExists('freelancer_profiles');
        Schema::dropIfExists('skills');
    }
};