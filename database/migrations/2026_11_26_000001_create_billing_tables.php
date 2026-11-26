<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();      // INV-2026-0001, set right after insert
            $table->string('public_token', 64)->unique();         // used by the public "pay online" link
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['draft', 'sent', 'paid', 'void'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['sandbox_card', 'manual']);
            $table->enum('status', ['succeeded', 'failed'])->default('succeeded');
            $table->string('reference')->nullable();               // gateway transaction id or bank note
            $table->string('card_last4', 4)->nullable();            // the ONLY card detail we ever store
            $table->string('failure_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // "Already billed" markers. Plain indexed columns (no foreign key) so this also works on SQLite tests.
        Schema::table('milestones', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
        });
        Schema::table('time_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', fn (Blueprint $t) => $t->dropColumn('invoice_id'));
        Schema::table('milestones', fn (Blueprint $t) => $t->dropColumn('invoice_id'));
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
