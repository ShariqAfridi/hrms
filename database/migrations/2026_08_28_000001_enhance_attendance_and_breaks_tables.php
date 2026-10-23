<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Update attendances table
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'total_break_minutes')) {
                $table->integer('total_break_minutes')->default(0)->after('total_minutes');
            }
            if (!Schema::hasColumn('attendances', 'net_work_minutes')) {
                $table->integer('net_work_minutes')->default(0)->after('total_break_minutes');
            }
            if (!Schema::hasColumn('attendances', 'current_break_type')) {
                $table->string('current_break_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendances', 'current_break_id')) {
                $table->uuid('current_break_id')->nullable()->after('current_break_type');
            }
            if (!Schema::hasColumn('attendances', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('device');
            }
            if (!Schema::hasColumn('attendances', 'location_verified')) {
                $table->boolean('location_verified')->default(false)->after('location_name');
            }
            if (!Schema::hasColumn('attendances', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('location_verified');
            }
            if (!Schema::hasColumn('attendances', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        // 2. Update attendance_breaks table
        Schema::table('attendance_breaks', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_breaks', 'break_type')) {
                $table->string('break_type')->default('LUNCH')->after('attendance_id');
            }
            if (!Schema::hasColumn('attendance_breaks', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(0)->after('end_time');
            }
            if (!Schema::hasColumn('attendance_breaks', 'exceeded_minutes')) {
                $table->integer('exceeded_minutes')->default(0)->after('duration_minutes');
            }
            if (!Schema::hasColumn('attendance_breaks', 'status')) {
                $table->string('status')->default('ACTIVE')->after('exceeded_minutes');
            }
            if (!Schema::hasColumn('attendance_breaks', 'notes')) {
                $table->string('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendance_breaks', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('attendance_breaks', 'created_at')) {
                $table->timestamps();
            }
        });

        // 3. Update notifications table if data column is missing
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'data')) {
                    $table->json('data')->nullable()->after('type');
                }
                if (!Schema::hasColumn('notifications', 'action_url')) {
                    $table->string('action_url')->nullable()->after('data');
                }
            });
        }
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'total_break_minutes', 'net_work_minutes', 'current_break_type',
                'current_break_id', 'ip_address', 'location_verified', 'latitude', 'longitude'
            ]);
        });

        Schema::table('attendance_breaks', function (Blueprint $table) {
            $table->dropColumn([
                'break_type', 'duration_minutes', 'exceeded_minutes', 'status', 'notes', 'ip_address'
            ]);
            $table->dropTimestamps();
        });

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn(['data', 'action_url']);
            });
        }
    }
};
