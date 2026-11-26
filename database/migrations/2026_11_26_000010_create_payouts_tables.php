<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();          // PO-2026-0001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();   // the freelancer
            $table->decimal('hours', 8, 2);
            $table->decimal('gross', 12, 2);        // hours x rate
            $table->decimal('commission', 12, 2);   // the agency's cut
            $table->decimal('net', 12, 2);          // what the freelancer receives
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('method')->nullable();                    // bank_transfer, bkash, nagad, cash, other
            $table->string('reference')->nullable();                 // transaction id or receipt number
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // One line per project, with the rate and commission that applied when the payout was made.
        Schema::create('payout_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->decimal('hours', 8, 2);
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('gross', 12, 2);
            $table->decimal('commission', 12, 2);
            $table->decimal('net', 12, 2);
            $table->timestamps();
        });

        // Marks hours that are already part of a payout. Plain indexed column so SQLite tests work too.
        Schema::table('time_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('payout_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', fn (Blueprint $t) => $t->dropColumn('payout_id'));
        Schema::dropIfExists('payout_lines');
        Schema::dropIfExists('payouts');
    }
};
