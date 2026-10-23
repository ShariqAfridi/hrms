<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->boolean('is_active')->default(true);

            $table->uuid('role_id');
            $table->uuid('organization_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('designation_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->uuid('payroll_id')->nullable();
            $table->uuid('manager_id')->nullable();

            // Bank details
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_iban')->nullable();
            $table->string('bank_branch_name')->nullable();
            $table->string('bank_branch_code')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('payment_method')->default('BANK_TRANSFER');

            // Salary overrides
            $table->double('base_salary')->nullable();
            $table->double('allowances')->nullable();
            $table->double('deductions')->nullable();

            // Profile info
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->boolean('two_factor_enabled')->default(false);

            // Identity & personal
            $table->dateTime('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('national_id_type')->nullable();
            $table->string('national_id_number')->nullable();
            $table->string('blood_group')->nullable();

            // Employment
            $table->dateTime('date_of_joining')->nullable();
            $table->string('employment_type')->nullable();
            $table->dateTime('probation_end_date')->nullable();
            $table->dateTime('confirmation_date')->nullable();

            // Reference
            $table->string('reference_name')->nullable();
            $table->string('reference_relationship')->nullable();
            $table->string('reference_phone')->nullable();
            $table->string('reference_email')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add foreign key for department head_user_id
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('head_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['head_user_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
        Schema::dropIfExists('users');
    }
};
