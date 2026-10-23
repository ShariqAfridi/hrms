<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Teams Table (Software House Teams: Web Dev, UI/UX Design, Graphic Design, QA, Project Management)
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('department_id');
            $table->string('name'); // Web Development, UI/UX Design, QA, Graphic Design, etc.
            $table->uuid('leader_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('leader_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add team_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'team_id')) {
                $table->uuid('team_id')->nullable()->after('department_id');
                $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            }
        });

        // 2. Projects Table (ClickUp Style: Web Projects & Design Projects)
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('project_manager_id')->nullable();
            $table->string('name');
            $table->string('client_name')->nullable();
            $table->string('type')->default('WEB'); // WEB, DESIGN, FULL_STACK, MOBILE
            $table->text('description')->nullable();
            $table->string('status')->default('IN_PROGRESS'); // PLANNING, IN_PROGRESS, IN_REVIEW, COMPLETED, ON_HOLD
            $table->integer('progress_percent')->default(0);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('project_manager_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('user_id');
            $table->string('role_in_project')->default('Developer'); // UI/UX Designer, Frontend Dev, Backend Dev, QA, etc.
            $table->integer('allocation_percentage')->default(100);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. ClickUp Style Tasks Table (With Priorities, Tracking, Progress & Assignees)
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('user_id')->nullable(); // Assigned Designer / Developer
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('WEB_DEV'); // WEB_DEV, UI_UX, GRAPHIC_DESIGN, QA
            $table->string('priority')->default('NORMAL'); // URGENT, HIGH, NORMAL, LOW
            $table->string('status')->default('TO_DO'); // TO_DO, IN_PROGRESS, REVIEW, COMPLETED
            $table->integer('progress_percent')->default(0);
            $table->integer('estimated_hours')->default(8);
            $table->dateTime('due_date')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }
        });

        Schema::dropIfExists('teams');
    }
};
