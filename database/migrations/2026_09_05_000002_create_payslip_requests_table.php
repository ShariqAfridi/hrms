<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payslip_requests')) {
            Schema::create('payslip_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('organization_id')->nullable();
                $table->string('payment_month', 7); // Format: YYYY-MM (e.g. '2026-09')
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED
                $table->uuid('reviewed_by_id')->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->uuid('payslip_id')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
                $table->foreign('reviewed_by_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('payslip_id')->references('id')->on('payslips')->onDelete('set null');

                $table->index(['user_id', 'payment_month']);
                $table->index(['organization_id', 'status']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_requests');
    }
};
