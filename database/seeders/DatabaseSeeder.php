<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Organization;
use App\Models\Branch;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Shift;
use App\Models\Payroll;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Team;
use App\Models\Task;
use App\Models\SalesTeam;
use App\Models\SalesTarget;
use App\Models\PerformanceKpi;
use App\Models\PerformanceReview;
use App\Models\Project;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $permissionsData = [
            ['key' => 'employee.read', 'domain' => 'employee', 'description' => 'View employee records'],
            ['key' => 'employee.create', 'domain' => 'employee', 'description' => 'Create employee records'],
            ['key' => 'employee.update', 'domain' => 'employee', 'description' => 'Update employee records'],
            ['key' => 'employee.delete', 'domain' => 'employee', 'description' => 'Delete employee records'],
            ['key' => 'payroll.read', 'domain' => 'payroll', 'description' => 'View payroll data'],
            ['key' => 'payroll.manage', 'domain' => 'payroll', 'description' => 'Create/update/archive payrolls'],
            ['key' => 'payroll.assign', 'domain' => 'payroll', 'description' => "Assign or reassign an employee's payroll"],
            ['key' => 'payroll.run', 'domain' => 'payroll', 'description' => 'Run payroll cycles'],
            ['key' => 'payroll.review', 'domain' => 'payroll', 'description' => 'Submit a payroll run for review'],
            ['key' => 'payroll.approve', 'domain' => 'payroll', 'description' => 'Approve payroll runs'],
            ['key' => 'payroll.finalize', 'domain' => 'payroll', 'description' => 'Finalize a payroll run and generate payslips'],
            ['key' => 'payroll.release', 'domain' => 'payroll', 'description' => 'Release a finalized payroll run to employees'],
            ['key' => 'salary_component.manage', 'domain' => 'payroll', 'description' => "Manage an employee's recurring salary components"],
            ['key' => 'overtime.request', 'domain' => 'payroll', 'description' => 'Submit an overtime request'],
            ['key' => 'overtime.approve', 'domain' => 'payroll', 'description' => 'Approve or reject overtime requests'],
            ['key' => 'bonus.manage', 'domain' => 'payroll', 'description' => 'Create and approve employee bonuses'],
            ['key' => 'loan.manage', 'domain' => 'payroll', 'description' => 'Create and approve employee loans/advances'],
            ['key' => 'attendance.read', 'domain' => 'attendance', 'description' => 'View attendance records'],
            ['key' => 'attendance.write', 'domain' => 'attendance', 'description' => 'Record/edit attendance'],
            ['key' => 'attendance.checkin', 'domain' => 'attendance', 'description' => 'Check in/out for attendance'],
            ['key' => 'attendance.read_all', 'domain' => 'attendance', 'description' => 'View all users attendance logs'],
            ['key' => 'leave.read', 'domain' => 'leave', 'description' => 'View leave requests'],
            ['key' => 'leave.apply', 'domain' => 'leave', 'description' => 'Apply for leaves'],
            ['key' => 'leave.approve', 'domain' => 'leave', 'description' => 'Approve/reject leave requests'],
            ['key' => 'settings.manage', 'domain' => 'settings', 'description' => 'Manage application settings'],
            ['key' => 'organization.manage', 'domain' => 'organization', 'description' => 'Manage organization/department structure'],
            ['key' => 'payroll.read_self', 'domain' => 'payroll', 'description' => 'View own payslips'],
            ['key' => 'recruitment.read', 'domain' => 'recruitment', 'description' => 'View job postings and candidates'],
            ['key' => 'recruitment.manage', 'domain' => 'recruitment', 'description' => 'Manage job postings and candidate pipeline'],
            ['key' => 'asset.read', 'domain' => 'asset', 'description' => 'View company assets'],
            ['key' => 'asset.assign', 'domain' => 'asset', 'description' => 'Assign/return company assets'],
        ];

        $permissionMap = [];
        foreach ($permissionsData as $p) {
            $perm = Permission::create([
                'id' => (string) Str::uuid(),
                'key' => $p['key'],
                'domain' => $p['domain'],
                'description' => $p['description'],
            ]);
            $permissionMap[$p['key']] = $perm->id;
        }

        $rolesData = [
            ['name' => 'SYSTEM_ADMIN', 'label' => 'System Admin', 'level' => 0, 'is_wildcard' => true, 'grants' => []],
            ['name' => 'HR_MANAGER', 'label' => 'Hr Manager', 'level' => 5, 'is_wildcard' => false, 'grants' => [
                'employee.read', 'employee.write', 'attendance.read', 'attendance.write', 'leave.approve', 'leave.read',
                'payroll.read', 'payroll.write', 'payroll.read_self', 'overtime.approve', 'bonus.manage', 'loan.manage',
                'recruitment.read', 'recruitment.write', 'asset.read', 'asset.assign'
            ]],
            ['name' => 'DEPARTMENT_HEAD', 'label' => 'Department Head', 'level' => 10, 'is_wildcard' => false, 'grants' => [
                'employee.read', 'attendance.read', 'attendance.write', 'leave.approve', 'leave.read',
                'payroll.read', 'payroll.read_self', 'overtime.approve', 'bonus.manage', 'loan.manage',
                'recruitment.read', 'asset.read', 'asset.assign'
            ]],
            ['name' => 'TEAM_LEAD', 'label' => 'Team Lead', 'level' => 20, 'is_wildcard' => false, 'grants' => [
                'employee.read', 'attendance.read', 'attendance.write', 'leave.approve', 'leave.read',
                'payroll.read_self', 'overtime.approve', 'asset.read', 'asset.assign'
            ]],
            ['name' => 'EMPLOYEE', 'label' => 'Employee', 'level' => 30, 'is_wildcard' => false, 'grants' => [
                'employee.read', 'attendance.checkin', 'attendance.read', 'leave.apply', 'leave.read',
                'payroll.read_self', 'overtime.request', 'asset.read'
            ]]
        ];

        $roleMap = [];
        foreach ($rolesData as $r) {
            $role = Role::create([
                'id' => (string) Str::uuid(),
                'name' => $r['name'],
                'label' => $r['label'],
                'level' => $r['level'],
                'is_wildcard' => $r['is_wildcard'],
                'is_system' => true
            ]);
            $roleMap[$r['name']] = $role;

            foreach ($r['grants'] as $permKey) {
                if (isset($permissionMap[$permKey])) {
                    RolePermission::create([
                        'id' => (string) Str::uuid(),
                        'role_id' => $role->id,
                        'permission_id' => $permissionMap[$permKey]
                    ]);
                }
            }
        }

        // Compatibility Aliases for Seed Data
        $roleMap['SUPER_ADMIN'] = $roleMap['SYSTEM_ADMIN'];
        $roleMap['ORG_ADMIN'] = $roleMap['SYSTEM_ADMIN'];
        $roleMap['HR'] = $roleMap['HR_MANAGER'];
        $roleMap['DEPT_MANAGER'] = $roleMap['DEPARTMENT_HEAD'];
        $roleMap['TEAM_MANAGER'] = $roleMap['TEAM_LEAD'];
        $roleMap['MANAGER'] = $roleMap['TEAM_LEAD'];

        $password = Hash::make('Password@123');

        // Create Global Super Admin User
        $superAdmin = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'superadmin@hrms.com',
            'password' => $password,
            'name' => 'Alexander Vance',
            'is_active' => true,
            'role_id' => $roleMap['SUPER_ADMIN']->id,
            'organization_id' => null,
            'date_of_joining' => now()->subYears(5),
            'employment_type' => 'FULL_TIME',
            'base_salary' => 800000,
        ]);

        User::create([
            'id' => (string) Str::uuid(),
            'email' => 'devel',
            'password' => Hash::make('Dev@ateeq26'),
            'name' => 'Developer',
            'is_active' => true,
            'role_id' => $roleMap['SUPER_ADMIN']->id,
            'organization_id' => null,
            'date_of_joining' => now(),
            'employment_type' => 'FULL_TIME',
            'base_salary' => 800000,
        ]);

        User::create([
            'id' => (string) Str::uuid(),
            'email' => 'devel@axecorp.com',
            'password' => Hash::make('Dev@ateeq26'),
            'name' => 'Developer',
            'is_active' => true,
            'role_id' => $roleMap['SUPER_ADMIN']->id,
            'organization_id' => null,
            'date_of_joining' => now(),
            'employment_type' => 'FULL_TIME',
            'base_salary' => 800000,
        ]);

        // ==========================================
        // COMPANY 1: AXECORP DIGITAL
        // ==========================================
        $org1 = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'AxeCorp Digital',
            'slug' => 'axecorp-digital',
            'legal_name' => 'AxeCorp Digital Solutions Pvt. Ltd.',
            'company_type' => 'Corporation',
            'registration_number' => 'REG-2019-004521',
            'tax_number' => 'TAX-88213-AXE',
            'email' => 'contact@axecorp.com',
            'phone' => '+92 42 3571 2200',
            'industry' => 'Technology & Software',
            'country' => 'Pakistan',
            'city' => 'Lahore',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'employee_size' => '51-200',
            'is_active' => true,
            'description' => 'Product engineering studio building enterprise software.',
            'about' => 'AxeCorp Digital designs and ships workforce management software.',
            'website' => 'https://axecorp.example.com',
            'attendance_late_threshold' => 15,
            'attendance_overtime_threshold' => 60,
            'leave_approvals_required' => 1,
            'payroll_tax_rate' => 12.0,
        ]);

        $hq1 = Branch::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'name' => 'Head Office', 'code' => 'AXE-HQ']);
        $loc1 = Location::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'branch_id' => $hq1->id, 'name' => 'Gulberg Complex - Floor 5', 'address' => '12-B Gulberg III, Lahore', 'latitude' => 31.5204, 'longitude' => 74.3587, 'radius_meter' => 150]);

        $deptExec1 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'name' => 'Management', 'code' => 'MGMT']);
        $deptSales1 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'name' => 'Sales', 'code' => 'SALES']);
        $deptProd1 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'name' => 'Production', 'code' => 'PROD']);
        $deptHR1 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'name' => 'HR & Administration', 'code' => 'HR-ADMIN']);

        $desigCEO1 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'Chief Executive Officer', 'code' => 'CEO']);
        $desigHR1 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'HR Manager', 'code' => 'HR-MGR']);
        $desigProdMgr = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'Production Manager', 'code' => 'PROD-MGR']);
        $desigWebPM = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'Web Project Manager', 'code' => 'WEB-PM']);
        $desigDesignPM = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'Design Project Manager', 'code' => 'DES-PM']);
        $desigEng1 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'Senior Software Engineer', 'code' => 'SR-SE']);
        $desigUIUX = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'UI/UX Designer', 'code' => 'UIUX-DES']);
        $desigQA = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'title' => 'QA Engineer', 'code' => 'QA-ENG']);

        $shift1 = Shift::create(['id' => (string) Str::uuid(), 'organization_id' => $org1->id, 'name' => 'General Shift', 'start_time' => '09:00', 'end_time' => '17:00', 'flexible' => false]);

        // Payrolls for AxeCorp Digital
        $payroll1_1 = Payroll::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'name' => 'AxeCorp Core Dev Payroll',
            'code' => 'AXE-DEV-PAY',
            'description' => 'Monthly payroll structure for core engineering & product development team.',
            'type' => 'MONTHLY',
            'pay_frequency' => 'MONTHLY',
            'currency' => 'PKR',
            'default_working_days' => 22,
            'base_salary' => 250000,
            'allowances' => 35000,
            'deductions' => 12000,
            'tax_rate' => 12.0,
            'status' => 'ACTIVE',
            'overtime_rate_type' => 'MULTIPLIER',
            'overtime_multiplier' => 1.5,
            'late_deduction_per_occurrence' => 500,
        ]);

        $payroll1_2 = Payroll::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'name' => 'AxeCorp Executive Payroll',
            'code' => 'AXE-EXEC-PAY',
            'description' => 'Executive leadership and C-suite salary structure with quarterly bonuses.',
            'type' => 'MONTHLY',
            'pay_frequency' => 'MONTHLY',
            'currency' => 'PKR',
            'default_working_days' => 22,
            'base_salary' => 550000,
            'allowances' => 75000,
            'deductions' => 25000,
            'tax_rate' => 15.0,
            'status' => 'ACTIVE',
            'overtime_rate_type' => 'MULTIPLIER',
            'overtime_multiplier' => 2.0,
            'late_deduction_per_occurrence' => 0,
        ]);

        // Employees for AxeCorp Digital
        $admin1 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'admin@axecorp.com',
            'password' => $password,
            'name' => 'Ahmed Raza',
            'is_active' => true,
            'role_id' => $roleMap['ORG_ADMIN']->id,
            'organization_id' => $org1->id,
            'department_id' => $deptExec1->id,
            'designation_id' => $desigCEO1->id,
            'location_id' => $loc1->id,
            'payroll_id' => $payroll1_2->id,
            'base_salary' => 600000,
            'allowances' => 75000,
            'deductions' => 25000,
            'date_of_joining' => now()->subYears(4),
            'employment_type' => 'FULL_TIME',
        ]);

        $hr1 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'bilal.ahmed@axecorp.com',
            'password' => $password,
            'name' => 'Bilal Ahmed',
            'is_active' => true,
            'role_id' => $roleMap['MANAGER']->id,
            'organization_id' => $org1->id,
            'department_id' => $deptHR1->id,
            'designation_id' => $desigHR1->id,
            'location_id' => $loc1->id,
            'payroll_id' => $payroll1_1->id,
            'base_salary' => 280000,
            'allowances' => 30000,
            'deductions' => 10000,
            'date_of_joining' => now()->subYears(3),
            'employment_type' => 'FULL_TIME',
        ]);

        $emp1 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'ali.raza@axecorp.com',
            'password' => $password,
            'name' => 'Ali Raza',
            'is_active' => true,
            'role_id' => $roleMap['EMPLOYEE']->id,
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'designation_id' => $desigEng1->id,
            'location_id' => $loc1->id,
            'payroll_id' => $payroll1_1->id,
            'manager_id' => $hr1->id,
            'base_salary' => 250000,
            'allowances' => 35000,
            'deductions' => 12000,
            'date_of_joining' => now()->subYears(2),
            'employment_type' => 'FULL_TIME',
        ]);

        // ==========================================
        // COMPANY 2: APEX LOGISTICS LTD
        // ==========================================
        $org2 = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Apex Logistics Ltd',
            'slug' => 'apex-logistics',
            'legal_name' => 'Apex Global Logistics Solutions Ltd.',
            'company_type' => 'Limited Liability',
            'registration_number' => 'REG-2021-998822',
            'tax_number' => 'TAX-44102-APEX',
            'email' => 'hr@apexlogistics.com',
            'phone' => '+92 42 3555 0199',
            'industry' => 'Logistics & Supply Chain',
            'country' => 'Pakistan',
            'city' => 'Karachi',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'employee_size' => '201-500',
            'is_active' => true,
            'description' => 'Freight forwarding, fleet management, and automated warehousing.',
            'about' => 'Apex Logistics connects regional supply chains across North America.',
            'website' => 'https://apexlogistics.example.com',
            'attendance_late_threshold' => 10,
            'attendance_overtime_threshold' => 45,
            'leave_approvals_required' => 2,
            'payroll_tax_rate' => 10.0,
        ]);

        $hq2 = Branch::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'name' => 'Central Hub', 'code' => 'APEX-HUB']);
        $loc2 = Location::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'branch_id' => $hq2->id, 'name' => 'Chicago Logistics Center', 'address' => '450 Cargo Way, Chicago, IL', 'latitude' => 41.8781, 'longitude' => -87.6298, 'radius_meter' => 300]);

        $deptOps2 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'name' => 'Operations & Fleet', 'code' => 'OPS']);
        $deptWare2 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'name' => 'Warehouse & Inventory', 'code' => 'WH']);

        $desigOpsMgr2 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'title' => 'Operations Director', 'code' => 'OPS-DIR']);
        $desigFleet2 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'title' => 'Fleet Logistics Specialist', 'code' => 'FLEET-SPEC']);
        $desigWarehouse2 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org2->id, 'title' => 'Warehouse Supervisor', 'code' => 'WH-SUP']);

        // Payrolls for Apex Logistics
        $payroll2_1 = Payroll::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org2->id,
            'name' => 'Apex Operations Payroll',
            'code' => 'APEX-OPS-PAY',
            'description' => 'Bi-weekly payroll cycle for fleet operators and route dispatch managers.',
            'type' => 'BI_WEEKLY',
            'pay_frequency' => 'BI_WEEKLY',
            'currency' => 'PKR',
            'default_working_days' => 14,
            'base_salary' => 180000,
            'allowances' => 20000,
            'deductions' => 8000,
            'tax_rate' => 10.0,
            'status' => 'ACTIVE',
            'overtime_rate_type' => 'MULTIPLIER',
            'overtime_multiplier' => 1.5,
            'late_deduction_per_occurrence' => 500,
        ]);

        $payroll2_2 = Payroll::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org2->id,
            'name' => 'Apex Warehouse Staff Payroll',
            'code' => 'APEX-WH-PAY',
            'description' => 'Weekly payroll structure for hourly shift warehouse staff.',
            'type' => 'WEEKLY',
            'pay_frequency' => 'WEEKLY',
            'currency' => 'PKR',
            'default_working_days' => 5,
            'base_salary' => 120000,
            'allowances' => 15000,
            'deductions' => 5000,
            'tax_rate' => 8.0,
            'status' => 'ACTIVE',
            'overtime_rate_type' => 'MULTIPLIER',
            'overtime_multiplier' => 1.5,
            'late_deduction_per_occurrence' => 400,
        ]);

        // Employees for Apex Logistics
        $admin2 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'tariq@apexlogistics.com',
            'password' => $password,
            'name' => 'Tariq Hassan',
            'is_active' => true,
            'role_id' => $roleMap['ORG_ADMIN']->id,
            'organization_id' => $org2->id,
            'department_id' => $deptOps2->id,
            'designation_id' => $desigOpsMgr2->id,
            'location_id' => $loc2->id,
            'payroll_id' => $payroll2_1->id,
            'base_salary' => 380000,
            'allowances' => 45000,
            'deductions' => 15000,
            'date_of_joining' => now()->subYears(3),
            'employment_type' => 'FULL_TIME',
        ]);

        $emp2_1 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'imran@apexlogistics.com',
            'password' => $password,
            'name' => 'Imran Khan',
            'is_active' => true,
            'role_id' => $roleMap['EMPLOYEE']->id,
            'organization_id' => $org2->id,
            'department_id' => $deptOps2->id,
            'designation_id' => $desigFleet2->id,
            'location_id' => $loc2->id,
            'payroll_id' => $payroll2_1->id,
            'manager_id' => $admin2->id,
            'base_salary' => 180000,
            'allowances' => 20000,
            'deductions' => 8000,
            'date_of_joining' => now()->subYears(1),
            'employment_type' => 'FULL_TIME',
        ]);

        $emp2_2 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'sara@apexlogistics.com',
            'password' => $password,
            'name' => 'Sara Malik',
            'is_active' => true,
            'role_id' => $roleMap['EMPLOYEE']->id,
            'organization_id' => $org2->id,
            'department_id' => $deptWare2->id,
            'designation_id' => $desigWarehouse2->id,
            'location_id' => $loc2->id,
            'payroll_id' => $payroll2_2->id,
            'manager_id' => $admin2->id,
            'base_salary' => 120000,
            'allowances' => 15000,
            'deductions' => 5000,
            'date_of_joining' => now()->subMonths(8),
            'employment_type' => 'FULL_TIME',
        ]);

        // ==========================================
        // COMPANY 3: NEXUS TECH GLOBAL
        // ==========================================
        $org3 = Organization::create([
            'id' => (string) Str::uuid(),
            'name' => 'Nexus Tech Global',
            'slug' => 'nexus-tech',
            'legal_name' => 'Nexus Tech Global Ltd.',
            'company_type' => 'Private Limited',
            'registration_number' => 'PK-88339100',
            'tax_number' => 'TAX-PK998231',
            'email' => 'careers@nexustech.io',
            'phone' => '+92 51 889 0011',
            'industry' => 'Artificial Intelligence',
            'country' => 'Pakistan',
            'city' => 'Islamabad',
            'timezone' => 'Asia/Karachi',
            'currency' => 'PKR',
            'employee_size' => '100-250',
            'is_active' => true,
            'description' => 'Deep tech lab pioneering generative AI & machine learning infrastructure.',
            'about' => 'Nexus Tech builds high-throughput LLM pipelines for enterprise partners.',
            'website' => 'https://nexustech.example.com',
            'attendance_late_threshold' => 20,
            'attendance_overtime_threshold' => 60,
            'leave_approvals_required' => 1,
            'payroll_tax_rate' => 18.0,
        ]);

        $hq3 = Branch::create(['id' => (string) Str::uuid(), 'organization_id' => $org3->id, 'name' => 'Islamabad Campus', 'code' => 'NEXUS-ISB']);
        $loc3 = Location::create(['id' => (string) Str::uuid(), 'organization_id' => $org3->id, 'branch_id' => $hq3->id, 'name' => 'Blue Area HQ', 'address' => 'Jinnah Avenue, Islamabad', 'latitude' => 33.7294, 'longitude' => 73.0931, 'radius_meter' => 100]);

        $deptAI3 = Department::create(['id' => (string) Str::uuid(), 'organization_id' => $org3->id, 'name' => 'AI Research & Engineering', 'code' => 'AI']);
        $desigAI3 = Designation::create(['id' => (string) Str::uuid(), 'organization_id' => $org3->id, 'title' => 'Lead AI Research Scientist', 'code' => 'LEAD-AI']);

        $payroll3_1 = Payroll::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org3->id,
            'name' => 'Nexus AI Engineering Payroll',
            'code' => 'NEXUS-AI-PAY',
            'description' => 'Monthly PKR-denominated payroll for research scientists and infrastructure engineers.',
            'type' => 'MONTHLY',
            'pay_frequency' => 'MONTHLY',
            'currency' => 'PKR',
            'default_working_days' => 20,
            'base_salary' => 450000,
            'allowances' => 50000,
            'deductions' => 20000,
            'tax_rate' => 18.0,
            'status' => 'ACTIVE',
            'overtime_rate_type' => 'MULTIPLIER',
            'overtime_multiplier' => 1.75,
            'late_deduction_per_occurrence' => 0,
        ]);

        $emp3_1 = User::create([
            'id' => (string) Str::uuid(),
            'email' => 'sophia@nexustech.io',
            'password' => $password,
            'name' => 'Sophia Chen',
            'is_active' => true,
            'role_id' => $roleMap['ORG_ADMIN']->id,
            'organization_id' => $org3->id,
            'department_id' => $deptAI3->id,
            'designation_id' => $desigAI3->id,
            'location_id' => $loc3->id,
            'payroll_id' => $payroll3_1->id,
            'base_salary' => 7500,
            'allowances' => 900,
            'deductions' => 350,
            'date_of_joining' => now()->subYears(2),
            'employment_type' => 'FULL_TIME',
        ]);

        // ==========================================
        // SAMPLE PAYROLL RUNS & PAYSLIPS GENERATION
        // ==========================================
        // Seed Payroll Run for AxeCorp HRMS
        $run1 = PayrollRun::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'payroll_id' => $payroll1_1->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'RELEASED',
            'approved_at' => now(),
            'released_at' => now(),
        ]);

        foreach ([$admin1, $hr1, $emp1] as $u) {
            $base = $u->base_salary ?? 4500;
            $allow = $u->allowances ?? 600;
            $ded = $u->deductions ?? 200;
            $tax = ($base + $allow - $ded) * 0.12;
            $net = $base + $allow - $ded - $tax;

            Payslip::create([
                'id' => (string) Str::uuid(),
                'payroll_run_id' => $run1->id,
                'user_id' => $u->id,
                'base_salary' => $base,
                'allowances' => $allow,
                'deductions' => $ded,
                'tax' => $tax,
                'net_pay' => $net,
                'created_at' => now(),
            ]);
        }

        // Seed Payroll Run for Apex Logistics
        $run2 = PayrollRun::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org2->id,
            'payroll_id' => $payroll2_1->id,
            'period_start' => now()->subDays(14),
            'period_end' => now(),
            'status' => 'RELEASED',
            'approved_at' => now(),
            'released_at' => now(),
        ]);

        foreach ([$admin2, $emp2_1] as $u) {
            $base = $u->base_salary ?? 3200;
            $allow = $u->allowances ?? 400;
            $ded = $u->deductions ?? 150;
            $tax = ($base + $allow - $ded) * 0.10;
            $net = $base + $allow - $ded - $tax;

            Payslip::create([
                'id' => (string) Str::uuid(),
                'payroll_run_id' => $run2->id,
                'user_id' => $u->id,
                'base_salary' => $base,
                'allowances' => $allow,
                'deductions' => $ded,
                'tax' => $tax,
                'net_pay' => $net,
                'created_at' => now(),
            ]);
        }

        // Seed Payroll Run for Nexus Tech
        $run3 = PayrollRun::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org3->id,
            'payroll_id' => $payroll3_1->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'status' => 'RELEASED',
            'approved_at' => now(),
            'released_at' => now(),
        ]);

        $base = $emp3_1->base_salary ?? 7500;
        $allow = $emp3_1->allowances ?? 900;
        $ded = $emp3_1->deductions ?? 350;
        $tax = ($base + $allow - $ded) * 0.18;
        $net = $base + $allow - $ded - $tax;

        Payslip::create([
            'id' => (string) Str::uuid(),
            'payroll_run_id' => $run3->id,
            'user_id' => $emp3_1->id,
            'base_salary' => $base,
            'allowances' => $allow,
            'deductions' => $ded,
            'tax' => $tax,
            'net_pay' => $net,
            'created_at' => now(),
        ]);

        // Seed Attendance Logs for Employees
        $sampleEmployees = [$emp1, $hr1, $emp2_1, $emp2_2, $emp3_1];
        foreach ($sampleEmployees as $emp) {
            if (!$emp) continue;

            // Today's log
            Attendance::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $emp->organization_id,
                'user_id' => $emp->id,
                'date' => date('Y-m-d'),
                'clock_in' => date('Y-m-d') . ' 08:55:00',
                'clock_out' => date('Y-m-d') . ' 17:30:00',
                'total_minutes' => 515,
                'overtime_minutes' => 35,
                'status' => 'PRESENT',
                'device' => 'Web',
            ]);

            // Yesterday's log
            Attendance::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $emp->organization_id,
                'user_id' => $emp->id,
                'date' => date('Y-m-d', strtotime('-1 day')),
                'clock_in' => date('Y-m-d', strtotime('-1 day')) . ' 09:25:00',
                'clock_out' => date('Y-m-d', strtotime('-1 day')) . ' 17:00:00',
                'total_minutes' => 455,
                'overtime_minutes' => 0,
                'status' => 'LATE',
                'device' => 'Mobile App',
            ]);
        }

        // =========================================================================
        // SEEDING EXPANDED ARCHITECTURE: TEAMS, SALES TARGETS, KPIS, PROJECTS
        // =========================================================================

        // 1. Teams Seeding
        $teamSales = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptExec1->id,
            'name' => 'PPC & Upwork Direct Sales Team',
            'leader_id' => $admin1->id,
            'description' => 'Outbound client acquisition, lead generation, and Upwork proposal management.',
        ]);

        // Production Teams
        $teamWebPM = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'name' => 'Web Project Management',
            'leader_id' => $hr1->id,
            'description' => 'Web sprint planning, developer allocation, and timeline management.',
        ]);

        $teamDesignPM = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'name' => 'Design Project Management',
            'leader_id' => $hr1->id,
            'description' => 'Design sprint planning, creative direction, and review workflows.',
        ]);

        $teamWebDev = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'name' => 'Web Development',
            'leader_id' => $emp1->id,
            'description' => 'Frontend, Backend, and Full-Stack Laravel Web Engineers.',
        ]);

        $teamUIUX = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'name' => 'UI/UX Design',
            'leader_id' => $emp1->id,
            'description' => 'Figma user flows, wireframes, and design systems.',
        ]);

        $teamGraphic = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'name' => 'Graphic Design',
            'leader_id' => $emp1->id,
            'description' => 'Brand identity, marketing creatives, and digital assets.',
        ]);

        $teamQA = Team::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'department_id' => $deptProd1->id,
            'name' => 'QA & Testing',
            'leader_id' => $emp1->id,
            'description' => 'Automated and Manual Quality Assurance.',
        ]);

        // Projects
        $proj1 = Project::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'project_manager_id' => $hr1->id,
            'name' => 'AxeCorp Web Portal',
            'client_name' => 'Internal Product',
            'type' => 'WEB',
            'status' => 'IN_PROGRESS',
            'start_date' => now()->subDays(10),
        ]);

        $proj2 = Project::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $org1->id,
            'project_manager_id' => $hr1->id,
            'name' => 'E-Commerce Mobile & Web Design',
            'client_name' => 'Laguna Store Inc.',
            'type' => 'DESIGN',
            'status' => 'IN_PROGRESS',
            'start_date' => now()->subDays(5),
        ]);

        // Tasks (ClickUp-Style Sprints)
        Task::create([
            'id' => (string) Str::uuid(),
            'project_id' => $proj1->id,
            'user_id' => $emp1->id,
            'title' => 'Implement Responsive Header & Navigation',
            'description' => 'Build high-performance Blade layout and mobile drawer navigation.',
            'category' => 'WEB_DEV',
            'priority' => 'NORMAL',
            'status' => 'COMPLETED',
            'progress_percent' => 100,
            'estimated_hours' => 12,
            'due_date' => now()->subDays(1),
        ]);

        Task::create([
            'id' => (string) Str::uuid(),
            'project_id' => $proj1->id,
            'user_id' => $emp1->id,
            'title' => 'Optimize Multi-Stage Payroll Locking Engine',
            'description' => 'Ensure locked payrolls cannot be modified without audit approval.',
            'category' => 'WEB_DEV',
            'priority' => 'HIGH',
            'status' => 'IN_PROGRESS',
            'progress_percent' => 60,
            'estimated_hours' => 16,
            'due_date' => now()->addDays(3),
        ]);

        Task::create([
            'id' => (string) Str::uuid(),
            'project_id' => $proj2->id,
            'user_id' => $emp1->id,
            'title' => 'Figma Design System & Mobile Prototypes',
            'description' => 'Design high-fidelity UI screens and mobile checkout flows.',
            'category' => 'UI_UX',
            'priority' => 'URGENT',
            'status' => 'TO_DO',
            'progress_percent' => 0,
            'estimated_hours' => 20,
            'due_date' => now()->addDays(5),
        ]);

        Task::create([
            'id' => (string) Str::uuid(),
            'project_id' => $proj1->id,
            'user_id' => $emp1->id,
            'title' => 'QA Regression Testing on Attendance Overtime',
            'description' => 'Verify 8-hour shift overtime calculations and late arrival grace period.',
            'category' => 'QA',
            'priority' => 'HIGH',
            'status' => 'REVIEW',
            'progress_percent' => 85,
            'estimated_hours' => 8,
            'due_date' => now()->addDays(2),
        ]);

        Task::create([
            'id' => (string) Str::uuid(),
            'project_id' => $proj2->id,
            'user_id' => $emp1->id,
            'title' => 'Brand Vector Logo & Social Media Kit',
            'description' => 'Export vector SVG assets, banners, and typography hierarchy.',
            'category' => 'GRAPHIC_DESIGN',
            'priority' => 'NORMAL',
            'status' => 'COMPLETED',
            'progress_percent' => 100,
            'estimated_hours' => 10,
            'due_date' => now()->subDays(2),
        ]);

    }
}

