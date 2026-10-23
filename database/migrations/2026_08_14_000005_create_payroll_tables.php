<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('MONTHLY');
            $table->string('pay_frequency')->default('MONTHLY');
            $table->string('currency')->default('PKR');
            $table->integer('default_working_days')->default(22);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('payroll_manager_id')->nullable();
            $table->double('base_salary')->default(0);
            $table->double('allowances')->default(0);
            $table->double('deductions')->default(0);
            $table->double('tax_rate')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->string('overtime_rate_type')->default('MULTIPLIER');
            $table->double('overtime_multiplier')->default(1.5);
            $table->double('overtime_fixed_rate')->nullable();
            $table->double('late_deduction_per_occurrence')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('payroll_manager_id')->references('id')->on('users')->onDelete('set null');
        });

        // Foreign key back to payrolls from users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('set null');
        });

        Schema::create('employee_payroll_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->uuid('previous_payroll_id')->nullable();
            $table->uuid('new_payroll_id');
            $table->uuid('changed_by_id');
            $table->dateTime('effective_date')->useCurrent();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('previous_payroll_id')->references('id')->on('payrolls')->onDelete('set null');
            $table->foreign('new_payroll_id')->references('id')->on('payrolls');
            $table->foreign('changed_by_id')->references('id')->on('users');
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('payroll_id');
            $table->dateTime('period_start');
            $table->dateTime('period_end');
            $table->string('status')->default('DRAFT');
            $table->uuid('reviewed_by_id')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->uuid('finalized_by_id')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->uuid('released_by_id')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('payroll_id')->references('id')->on('payrolls');
        });

        Schema::create('payroll_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_id');
            $table->string('description');
            $table->double('amount');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->onDelete('cascade');
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_run_id');
            $table->uuid('user_id');
            $table->double('base_salary');
            $table->double('allowances')->default(0.0);
            $table->double('deductions')->default(0.0);
            $table->double('tax')->default(0.0);
            $table->double('net_pay');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('payslip_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payslip_id');
            $table->string('category');
            $table->string('source');
            $table->string('source_id')->nullable();
            $table->string('label');
            $table->double('amount');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('payslip_id')->references('id')->on('payslips')->onDelete('cascade');
        });

        Schema::create('salary_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->string('category');
            $table->string('name');
            $table->string('amount_type')->default('FIXED');
            $table->double('amount');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->dateTime('date');
            $table->double('hours');
            $table->text('reason')->nullable();
            $table->string('status')->default('PENDING');
            $table->uuid('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('bonuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->string('type')->default('CUSTOM');
            $table->string('title');
            $table->string('amount_type')->default('FIXED');
            $table->double('amount');
            $table->dateTime('effective_date');
            $table->string('status')->default('PENDING');
            $table->uuid('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('loan_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->double('min_amount')->default(0);
            $table->double('max_amount')->default(5000);
            $table->double('interest_rate')->default(0);
            $table->string('interest_type')->default('NO_INTEREST');
            $table->integer('min_duration')->default(1);
            $table->integer('max_duration')->default(12);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('loan_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->unique();
            $table->integer('max_active_loans')->default(2);
            $table->integer('grace_period_days')->default(5);
            $table->double('late_payment_penalty_rate')->default(2);
            $table->double('early_settlement_penalty_rate')->default(1);
            $table->double('early_settlement_discount_rate')->default(0);
            $table->string('repayment_frequency')->default('MONTHLY');
            $table->boolean('payroll_deduction_enabled')->default(true);
            $table->integer('approval_levels')->default(2);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('loan_number')->unique();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->uuid('loan_type_id');
            $table->double('principal_amount');
            $table->double('interest_rate')->default(0);
            $table->string('interest_type')->default('NO_INTEREST');
            $table->double('total_interest')->default(0);
            $table->double('total_repayable');
            $table->integer('installments');
            $table->double('installment_amount');
            $table->double('remaining_balance');
            $table->text('reason')->nullable();
            $table->string('status')->default('DRAFT');
            $table->uuid('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->double('disbursed_amount')->nullable();
            $table->dateTime('disbursed_at')->nullable();
            $table->string('disbursing_bank')->nullable();
            $table->string('disbursement_reference')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('loan_type_id')->references('id')->on('loan_types');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->integer('installment_number');
            $table->dateTime('due_date');
            $table->double('principal_amount');
            $table->double('interest_amount')->default(0);
            $table->double('total_amount');
            $table->double('paid_amount')->default(0);
            $table->string('status')->default('PENDING');
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('installment_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->double('amount');
            $table->dateTime('payment_date');
            $table->string('method')->default('PAYROLL_DEDUCTION');
            $table->string('reference')->nullable();
            $table->uuid('recorded_by_id')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('installment_id')->references('id')->on('loan_installments')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('recorded_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('loan_settlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->double('settlement_amount');
            $table->double('discount_amount')->default(0);
            $table->double('penalty_amount')->default(0);
            $table->string('status')->default('PENDING');
            $table->text('reason')->nullable();
            $table->uuid('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('approved_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_settlements');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_policies');
        Schema::dropIfExists('loan_types');
        Schema::dropIfExists('bonuses');
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('payslip_line_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_ledger_entries');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_payroll_histories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['payroll_id']);
        });
        Schema::dropIfExists('payrolls');
    }
};
