<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'team_manager_id')) {
                $table->uuid('team_manager_id')->nullable()->after('current_approver_id');
            }
            if (!Schema::hasColumn('leave_requests', 'dept_manager_id')) {
                $table->uuid('dept_manager_id')->nullable()->after('team_manager_id');
            }
            if (!Schema::hasColumn('leave_requests', 'forwarded_by')) {
                $table->uuid('forwarded_by')->nullable()->after('dept_manager_id');
            }
            if (!Schema::hasColumn('leave_requests', 'forwarded_at')) {
                $table->dateTime('forwarded_at')->nullable()->after('forwarded_by');
            }
            if (!Schema::hasColumn('leave_requests', 'forward_notes')) {
                $table->text('forward_notes')->nullable()->after('forwarded_at');
            }
            if (!Schema::hasColumn('leave_requests', 'approved_by')) {
                $table->uuid('approved_by')->nullable()->after('forward_notes');
            }
            if (!Schema::hasColumn('leave_requests', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('leave_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('leave_requests', 'revoked_by')) {
                $table->uuid('revoked_by')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('leave_requests', 'revoked_at')) {
                $table->dateTime('revoked_at')->nullable()->after('revoked_by');
            }
            if (!Schema::hasColumn('leave_requests', 'revocation_reason')) {
                $table->text('revocation_reason')->nullable()->after('revoked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $columns = [
                'team_manager_id', 'dept_manager_id', 'forwarded_by', 'forwarded_at',
                'forward_notes', 'approved_by', 'approved_at', 'rejection_reason',
                'revoked_by', 'revoked_at', 'revocation_reason'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('leave_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
