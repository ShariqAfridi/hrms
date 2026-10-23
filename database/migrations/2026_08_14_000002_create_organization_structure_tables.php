<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('company_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable()->default('');
            $table->string('company_type')->nullable()->default('');
            $table->string('registration_number')->nullable()->default('');
            $table->string('tax_number')->nullable()->default('');
            $table->string('email')->nullable()->default('');
            $table->string('phone')->nullable()->default('');
            $table->string('industry')->nullable()->default('');
            $table->string('country')->nullable()->default('');
            $table->string('city')->nullable()->default('');
            $table->string('timezone')->nullable()->default('UTC');
            $table->string('currency')->default('PKR');
            $table->string('employee_size')->nullable()->default('');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('about')->nullable();
            $table->string('website')->nullable()->default('');

            // System settings
            $table->string('email_config_host')->nullable();
            $table->integer('email_config_port')->nullable();
            $table->string('email_config_user')->nullable();
            $table->string('email_config_password')->nullable();
            $table->integer('attendance_late_threshold')->default(15);
            $table->integer('attendance_overtime_threshold')->default(60);
            $table->integer('leave_approvals_required')->default(1);
            $table->double('payroll_tax_rate')->default(10.0);

            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('code');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('branch_id');
            $table->string('name');
            $table->text('address');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->double('radius_meter')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->uuid('parent_department_id')->nullable();
            $table->uuid('head_user_id')->nullable()->unique();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_department_id')->references('id')->on('departments')->onDelete('set null');
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('title');
            $table->string('code');
            $table->timestamps();

            $table->unique(['organization_id', 'title']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('start_time');
            $table->string('end_time');
            $table->boolean('flexible')->default(false);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('designations');
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_department_id']);
        });
        Schema::dropIfExists('departments');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('company_types');
        Schema::dropIfExists('industries');
    }
};
