<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (!Schema::hasColumn('payslips', 'late_deduction')) {
                $table->decimal('late_deduction', 12, 2)->default(0)->after('advance_deduction');
            }
            if (!Schema::hasColumn('payslips', 'late_days_deducted')) {
                $table->integer('late_days_deducted')->default(0)->after('late_deduction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (Schema::hasColumn('payslips', 'late_deduction')) {
                $table->dropColumn('late_deduction');
            }
            if (Schema::hasColumn('payslips', 'late_days_deducted')) {
                $table->dropColumn('late_days_deducted');
            }
        });
    }
};
