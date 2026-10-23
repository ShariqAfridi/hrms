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
        // Add sensitive_changes_count to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sensitive_changes_count')) {
                $table->unsignedInteger('sensitive_changes_count')->default(0)->after('is_active');
            }
        });

        // Create profile_change_requests table
        if (!Schema::hasTable('profile_change_requests')) {
            Schema::create('profile_change_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('type')->default('BANK_AND_NIC');
                $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED, CANCELLED
                $table->json('requested_changes');
                $table->json('current_values')->nullable();
                $table->text('reason')->nullable();
                $table->uuid('reviewed_by_id')->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('reviewed_by_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['user_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_change_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sensitive_changes_count')) {
                $table->dropColumn('sensitive_changes_count');
            }
        });
    }
};
