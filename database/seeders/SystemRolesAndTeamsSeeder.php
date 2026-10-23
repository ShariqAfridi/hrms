<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemRolesAndTeamsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed / Update System Roles (Strict 5 Core Roles)
        $roles = [
            [
                'name' => 'SYSTEM_ADMIN',
                'label' => 'System Admin',
                'level' => 0,
                'is_wildcard' => true,
                'is_system' => true,
            ],
            [
                'name' => 'HR_MANAGER',
                'label' => 'Hr Manager',
                'level' => 5,
                'is_wildcard' => false,
                'is_system' => true,
            ],
            [
                'name' => 'DEPARTMENT_HEAD',
                'label' => 'Department Head',
                'level' => 10,
                'is_wildcard' => false,
                'is_system' => true,
            ],
            [
                'name' => 'TEAM_LEAD',
                'label' => 'Team Lead',
                'level' => 20,
                'is_wildcard' => false,
                'is_system' => true,
            ],
            [
                'name' => 'EMPLOYEE',
                'label' => 'Employee',
                'level' => 30,
                'is_wildcard' => false,
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            $existing = Role::where('name', $roleData['name'])->first();
            if ($existing) {
                $existing->update([
                    'label' => $roleData['label'],
                    'level' => $roleData['level'],
                    'is_wildcard' => $roleData['is_wildcard'],
                    'is_system' => $roleData['is_system'],
                ]);
            } else {
                Role::create([
                    'id' => (string) Str::uuid(),
                    'name' => $roleData['name'],
                    'label' => $roleData['label'],
                    'level' => $roleData['level'],
                    'is_wildcard' => $roleData['is_wildcard'],
                    'is_system' => $roleData['is_system'],
                ]);
            }
        }

        // 2. Ensure Production and Sales Departments and their specialized teams exist
        $orgs = Organization::all();
        if ($orgs->isEmpty()) {
            return;
        }

        foreach ($orgs as $org) {
            // Production Department
            $prodDept = Department::firstOrCreate(
                ['organization_id' => $org->id, 'name' => 'Production'],
                ['code' => 'PROD']
            );

            // Production Teams: SEO, Web Development, Design, Animation
            $prodTeams = [
                'SEO' => 'Search Engine Optimization & Digital Marketing Squad',
                'Web Development' => 'Web, Frontend & Full-Stack Development Squad',
                'Design' => 'UI/UX & Creative Graphic Design Squad',
                'Animation' => '2D/3D Animation & Motion Graphics Squad',
            ];

            foreach ($prodTeams as $teamName => $desc) {
                Team::firstOrCreate(
                    ['organization_id' => $org->id, 'department_id' => $prodDept->id, 'name' => $teamName],
                    ['description' => $desc]
                );
            }

            // Sales Department
            $salesDept = Department::firstOrCreate(
                ['organization_id' => $org->id, 'name' => 'Sales'],
                ['code' => 'SALES']
            );

            // Sales Teams: Book, Design & Development
            $salesTeams = [
                'Book' => 'Book Writing & Publishing Sales Squad',
                'Design & Development' => 'Design & Web Development Tech Sales Squad',
            ];

            foreach ($salesTeams as $teamName => $desc) {
                Team::firstOrCreate(
                    ['organization_id' => $org->id, 'department_id' => $salesDept->id, 'name' => $teamName],
                    ['description' => $desc]
                );
            }
        }
    }
}
