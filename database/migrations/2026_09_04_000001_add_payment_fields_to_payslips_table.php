<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (!Schema::hasColumn('payslips', 'payment_month')) {
                $table->string('payment_month', 10)->nullable()->after('pay_date');
            }
            if (!Schema::hasColumn('payslips', 'status')) {
                $table->string('status', 50)->default('PAID')->after('payment_month');
            }
            if (!Schema::hasColumn('payslips', 'payment_method')) {
                $table->string('payment_method', 50)->default('BANK_TRANSFER')->after('status');
            }
            if (!Schema::hasColumn('payslips', 'template_style')) {
                $table->string('template_style', 50)->default('axecorp')->after('payment_method');
            }
            if (!Schema::hasColumn('payslips', 'notes')) {
                $table->text('notes')->nullable()->after('template_style');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $cols = ['payment_month', 'status', 'payment_method', 'template_style', 'notes'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('payslips', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
