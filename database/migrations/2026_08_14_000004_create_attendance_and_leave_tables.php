<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->uuid('user_id');
            $table->uuid('shift_id')->nullable();
            $table->dateTime('date');
            $table->dateTime('clock_in');
            $table->dateTime('clock_out')->nullable();
            $table->integer('total_minutes')->default(0);
            $table->string('status')->default('PRESENT');
            $table->integer('overtime_minutes')->default(0);
            $table->string('approval_status')->default('APPROVED');
            $table->text('remarks')->nullable();
            $table->string('device')->default('Web');
            $table->string('location_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
        });

        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attendance_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->integer('days_allocated');
            $table->string('color')->default('#3b82f6');
            $table->string('icon')->default('Calendar');
            $table->boolean('is_paid')->default(true);
            $table->integer('max_annual_limit')->default(12);
            $table->boolean('carry_forward')->default(false);
            $table->boolean('encashment')->default(false);
            $table->string('gender')->nullable();
            $table->integer('min_service_months')->default(0);
            $table->boolean('attachment_required')->default(false);
            $table->text('approval_workflow')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->uuid('leave_type_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->string('session')->nullable();
            $table->double('total_days')->default(1.0);
            $table->text('reason')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('attachment_url')->nullable();
            $table->boolean('notify_hr')->default(false);
            $table->text('comments')->nullable();
            $table->uuid('current_approver_id')->nullable();
            $table->text('approval_history')->nullable();
            $table->string('status')->default('PENDING');
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->foreign('current_approver_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance_breaks');
        Schema::dropIfExists('attendances');
    }
};
