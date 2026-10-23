<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure single unified "Leaves" type exists with 12 days allocation
        $primaryLeaveType = DB::table('leave_types')->where('name', 'Leaves')->first();

        if (!$primaryLeaveType) {
            $first = DB::table('leave_types')->first();
            if ($first) {
                DB::table('leave_types')->where('id', $first->id)->update([
                    'name' => 'Leaves',
                    'days_allocated' => 12,
                    'color' => '#6366f1',
                    'is_paid' => 1,
                    'updated_at' => now(),
                ]);
                $primaryId = $first->id;
            } else {
                $orgId = DB::table('organizations')->value('id');
                $primaryId = (string) Str::uuid();
                DB::table('leave_types')->insert([
                    'id' => $primaryId,
                    'organization_id' => $orgId,
                    'name' => 'Leaves',
                    'days_allocated' => 12,
                    'color' => '#6366f1',
                    'is_paid' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            $primaryId = $primaryLeaveType->id;
            DB::table('leave_types')->where('id', $primaryId)->update([
                'days_allocated' => 12,
                'color' => '#6366f1',
                'is_paid' => 1,
                'updated_at' => now(),
            ]);
        }

        // 2. Re-point any existing leave requests to the single unified Leaves type
        DB::table('leave_requests')->where('leave_type_id', '!=', $primaryId)->update([
            'leave_type_id' => $primaryId,
        ]);

        // 3. Delete any other redundant leave types (Casual, Annual, Maternity, Sick)
        DB::table('leave_types')->where('id', '!=', $primaryId)->delete();

        // 4. Update system settings to reflect single 12-day quota
        if (DB::getSchemaBuilder()->hasTable('system_settings')) {
            DB::table('system_settings')->whereIn('key', [
                'leave_annual_quota', 'leave_casual_quota', 'leave_sick_quota', 'leave_maternity_quota'
            ])->delete();

            DB::table('system_settings')->updateOrInsert(
                ['key' => 'leave_total_quota'],
                [
                    'id' => (string) Str::uuid(),
                    'group' => 'leave',
                    'label' => 'Standard Annual Leaves Quota (Days)',
                    'value' => '12',
                    'type' => 'number',
                    'description' => 'Fixed yearly leave days allocated per employee (12 days total, no separate types).',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // No-op rollback
    }
};
