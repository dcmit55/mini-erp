<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;
use Spatie\Permission\Models\Permission;
use App\Support\Permissions;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Create all permissions ───────────────────────────────────────
        foreach (Permissions::all() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── 2. Define roles & their permissions ────────────────────────────
        $roleMap = [

            // Super admin: akses penuh ke semua permission
            'super_admin' => Permissions::all(),

            // Admin (read-only): hanya view & export
            'admin' => array_filter(Permissions::all(), fn($p) =>
                str_ends_with($p, '.view') || str_ends_with($p, '.export')
            ),

            // Admin HR: full HR + holidays + departments view
            'admin_hr' => [
                Permissions::HR_DASHBOARD_VIEW,
                Permissions::HR_EMPLOYEES_VIEW,
                Permissions::HR_EMPLOYEES_CREATE,
                Permissions::HR_EMPLOYEES_EDIT,
                Permissions::HR_EMPLOYEES_DELETE,
                Permissions::HR_EMPLOYEES_IMPORT,
                Permissions::HR_ATTENDANCE_VIEW,
                Permissions::HR_ATTENDANCE_EDIT,
                Permissions::HR_ATTENDANCE_EXPORT,
                Permissions::HR_FINGERSPOT_VIEW,
                Permissions::HR_FINGERSPOT_REGISTER,
                Permissions::HR_LEAVE_VIEW,
                Permissions::HR_LEAVE_CREATE,
                Permissions::HR_LEAVE_APPROVE_HR,
                Permissions::HR_OVERTIME_VIEW,
                Permissions::HR_OVERTIME_CREATE,
                Permissions::HR_OVERTIME_APPROVE,
                Permissions::HR_OVERTIME_PAY_VIEW,
                Permissions::HR_OVERTIME_PAY_EDIT,
                Permissions::HR_WARNING_LETTER_VIEW,
                Permissions::HR_WARNING_LETTER_CREATE,
                Permissions::HR_WARNING_LETTER_EDIT,
                Permissions::HR_WARNING_LETTER_DELETE,
                Permissions::HR_WARNING_BATCH_VIEW,
                Permissions::HR_WARNING_BATCH_CREATE,
                Permissions::ADMIN_DEPARTMENTS_VIEW,
                Permissions::ADMIN_DEPARTMENTS_EDIT,
                Permissions::ADMIN_HOLIDAYS_VIEW,
                Permissions::ADMIN_HOLIDAYS_EDIT,
                Permissions::PRODUCTION_TIMING_MONITOR_VIEW,
            ],

            // Admin Logistic: full logistic + view JO + projects + material planning + dept leave approval
            'admin_logistic' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_CREATE,
                Permissions::PRODUCTION_MATERIAL_PLANNING_EDIT,
                Permissions::PRODUCTION_MATERIAL_PLANNING_DELETE,
                Permissions::LOGISTIC_INVENTORY_VIEW,
                Permissions::LOGISTIC_INVENTORY_CREATE,
                Permissions::LOGISTIC_INVENTORY_EDIT,
                Permissions::LOGISTIC_INVENTORY_DELETE,
                Permissions::LOGISTIC_INVENTORY_EXPORT,
                Permissions::LOGISTIC_INVENTORY_IMPORT,
                Permissions::LOGISTIC_INVENTORY_BATCH_VIEW,
                Permissions::LOGISTIC_GOODS_IN_VIEW,
                Permissions::LOGISTIC_GOODS_IN_CREATE,
                Permissions::LOGISTIC_GOODS_IN_EDIT,
                Permissions::LOGISTIC_GOODS_OUT_VIEW,
                Permissions::LOGISTIC_GOODS_OUT_CREATE,
                Permissions::LOGISTIC_GOODS_OUT_EDIT,
                Permissions::FINANCE_CURRENCY_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_CREATE,
                Permissions::LOGISTIC_MR_VIEW,
                Permissions::LOGISTIC_MR_CREATE,
                Permissions::LOGISTIC_MR_EDIT,
                Permissions::LOGISTIC_MR_DELETE,
                Permissions::LOGISTIC_MR_APPROVE,
                Permissions::LOGISTIC_MR_EXPORT,
                Permissions::LOGISTIC_MATERIAL_USAGE_VIEW,
                Permissions::LOGISTIC_MATERIAL_USAGE_CREATE,
                Permissions::LOGISTIC_SHORTAGE_VIEW,
                Permissions::LOGISTIC_SHORTAGE_EDIT,
                Permissions::PROCUREMENT_PO_VIEW,
                Permissions::PROCUREMENT_SHIPPING_VIEW,
                Permissions::PROCUREMENT_SHIPPING_EDIT,
                Permissions::PROCUREMENT_SUPPLIER_VIEW,
                Permissions::PROCUREMENT_SUPPLIER_EDIT,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::PRODUCTION_JO_EXPORT,
                Permissions::ADMIN_DEPARTMENTS_VIEW,
                Permissions::LARK_STAGING_VIEW,
                Permissions::LARK_STAGING_SYNC,
                Permissions::LARK_STAGING_APPROVE,
                Permissions::HR_LEAVE_VIEW,
                Permissions::HR_LEAVE_DEPT_APPROVE_LOGISTIC,
            ],

            // Admin Finance: full finance + full procurement + view logistic + view projects
            'admin_finance' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_VIEW,
                Permissions::LOGISTIC_GOODS_IN_VIEW,
                Permissions::LOGISTIC_GOODS_OUT_VIEW,
                Permissions::FINANCE_COSTING_VIEW,
                Permissions::FINANCE_COSTING_EDIT,
                Permissions::FINANCE_CURRENCY_VIEW,
                Permissions::FINANCE_CURRENCY_EDIT,
                Permissions::FINANCE_KASBON_VIEW,
                Permissions::FINANCE_KASBON_APPROVE,
                Permissions::FINANCE_PURCHASE_EDITED_VIEW,
                Permissions::FINANCE_PURCHASE_EDITED_EDIT,
                Permissions::PROCUREMENT_PO_VIEW,
                Permissions::PROCUREMENT_PO_CREATE,
                Permissions::PROCUREMENT_PO_EDIT,
                Permissions::PROCUREMENT_PO_DELETE,
                Permissions::PROCUREMENT_PO_APPROVE,
                Permissions::PROCUREMENT_SUPPLIER_VIEW,
                Permissions::PROCUREMENT_SUPPLIER_EDIT,
                Permissions::LOGISTIC_INVENTORY_VIEW,
                Permissions::LOGISTIC_MR_VIEW,
                Permissions::LOGISTIC_MR_EXPORT,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::ADMIN_DEPARTMENTS_VIEW,
            ],

            // Admin Procurement: full procurement + view inventory & JO + view projects (approve hanya finance)
            'admin_procurement' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PROCUREMENT_PO_VIEW,
                Permissions::PROCUREMENT_PO_CREATE,
                Permissions::PROCUREMENT_PO_EDIT,
                Permissions::PROCUREMENT_PO_DELETE,
                Permissions::PROCUREMENT_SHIPPING_VIEW,
                Permissions::PROCUREMENT_SHIPPING_EDIT,
                Permissions::PROCUREMENT_SUPPLIER_VIEW,
                Permissions::PROCUREMENT_SUPPLIER_EDIT,
                Permissions::LOGISTIC_INVENTORY_VIEW,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::ADMIN_DEPARTMENTS_VIEW,
            ],

            // Admin Mascot: mascot timing + view JO + projects + material planning + monitor + full logistic + dept leave approval
            'admin_mascot' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_CREATE,
                Permissions::PRODUCTION_MATERIAL_PLANNING_EDIT,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::LOGISTIC_GOODS_IN_VIEW,
                Permissions::LOGISTIC_GOODS_IN_CREATE,
                Permissions::LOGISTIC_GOODS_OUT_VIEW,
                Permissions::LOGISTIC_GOODS_OUT_CREATE,
                Permissions::PRODUCTION_MASCOT_TIMING_VIEW,
                Permissions::PRODUCTION_MASCOT_TIMING_EDIT,
                Permissions::PRODUCTION_TIMING_MONITOR_VIEW,
                Permissions::PRODUCTION_PERFORMANCE_VIEW,
                Permissions::PRODUCTION_EFFICIENCY_VIEW,
                Permissions::HR_OVERTIME_VIEW,
                Permissions::HR_OVERTIME_CREATE,
                Permissions::HR_LEAVE_VIEW,
                Permissions::HR_LEAVE_DEPT_APPROVE_MASCOT,
                Permissions::LOGISTIC_INVENTORY_VIEW,
                Permissions::LOGISTIC_INVENTORY_CREATE,
                Permissions::LOGISTIC_INVENTORY_EDIT,
                Permissions::LOGISTIC_INVENTORY_DELETE,
                Permissions::LOGISTIC_INVENTORY_EXPORT,
                Permissions::LOGISTIC_INVENTORY_IMPORT,
                Permissions::LOGISTIC_INVENTORY_BATCH_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_CREATE,
                Permissions::LOGISTIC_MR_VIEW,
                Permissions::LOGISTIC_MR_CREATE,
                Permissions::LOGISTIC_MR_EDIT,
                Permissions::LOGISTIC_MR_DELETE,
                Permissions::LOGISTIC_MR_APPROVE,
                Permissions::LOGISTIC_MR_EXPORT,
                Permissions::LOGISTIC_MATERIAL_USAGE_VIEW,
                Permissions::LOGISTIC_MATERIAL_USAGE_CREATE,
                Permissions::LOGISTIC_SHORTAGE_VIEW,
                Permissions::LOGISTIC_SHORTAGE_EDIT,
            ],

            // Admin Costume: costume timing + view JO + projects + material planning + monitor + full logistic + dept leave approval
            'admin_costume' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_CREATE,
                Permissions::PRODUCTION_MATERIAL_PLANNING_EDIT,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::LOGISTIC_GOODS_IN_VIEW,
                Permissions::LOGISTIC_GOODS_IN_CREATE,
                Permissions::LOGISTIC_GOODS_OUT_VIEW,
                Permissions::LOGISTIC_GOODS_OUT_CREATE,
                Permissions::PRODUCTION_COSTUME_TIMING_VIEW,
                Permissions::PRODUCTION_COSTUME_TIMING_EDIT,
                Permissions::PRODUCTION_TIMING_MONITOR_VIEW,
                Permissions::PRODUCTION_PERFORMANCE_VIEW,
                Permissions::PRODUCTION_EFFICIENCY_VIEW,
                Permissions::HR_OVERTIME_VIEW,
                Permissions::HR_OVERTIME_CREATE,
                Permissions::HR_LEAVE_VIEW,
                Permissions::HR_LEAVE_DEPT_APPROVE_COSTUME,
                Permissions::LOGISTIC_INVENTORY_VIEW,
                Permissions::LOGISTIC_INVENTORY_CREATE,
                Permissions::LOGISTIC_INVENTORY_EDIT,
                Permissions::LOGISTIC_INVENTORY_DELETE,
                Permissions::LOGISTIC_INVENTORY_EXPORT,
                Permissions::LOGISTIC_INVENTORY_IMPORT,
                Permissions::LOGISTIC_INVENTORY_BATCH_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_CREATE,
                Permissions::LOGISTIC_MR_VIEW,
                Permissions::LOGISTIC_MR_CREATE,
                Permissions::LOGISTIC_MR_EDIT,
                Permissions::LOGISTIC_MR_DELETE,
                Permissions::LOGISTIC_MR_APPROVE,
                Permissions::LOGISTIC_MR_EXPORT,
                Permissions::LOGISTIC_MATERIAL_USAGE_VIEW,
                Permissions::LOGISTIC_MATERIAL_USAGE_CREATE,
                Permissions::LOGISTIC_SHORTAGE_VIEW,
                Permissions::LOGISTIC_SHORTAGE_EDIT,
            ],

            // Admin Animatronic: animatronics timing + view JO + projects + material planning + monitor + full logistic + dept leave approval
            'admin_animatronic' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_VIEW,
                Permissions::PRODUCTION_MATERIAL_PLANNING_CREATE,
                Permissions::PRODUCTION_MATERIAL_PLANNING_EDIT,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::LOGISTIC_GOODS_IN_VIEW,
                Permissions::LOGISTIC_GOODS_IN_CREATE,
                Permissions::LOGISTIC_GOODS_OUT_VIEW,
                Permissions::LOGISTIC_GOODS_OUT_CREATE,
                Permissions::PRODUCTION_ANIMATRONICS_TIMING_VIEW,
                Permissions::PRODUCTION_ANIMATRONICS_TIMING_EDIT,
                Permissions::PRODUCTION_TIMING_MONITOR_VIEW,
                Permissions::PRODUCTION_PERFORMANCE_VIEW,
                Permissions::PRODUCTION_EFFICIENCY_VIEW,
                Permissions::HR_OVERTIME_VIEW,
                Permissions::HR_OVERTIME_CREATE,
                Permissions::HR_LEAVE_VIEW,
                Permissions::HR_LEAVE_DEPT_APPROVE_ANIMATRONIC,
                Permissions::LOGISTIC_INVENTORY_VIEW,
                Permissions::LOGISTIC_INVENTORY_CREATE,
                Permissions::LOGISTIC_INVENTORY_EDIT,
                Permissions::LOGISTIC_INVENTORY_DELETE,
                Permissions::LOGISTIC_INVENTORY_EXPORT,
                Permissions::LOGISTIC_INVENTORY_IMPORT,
                Permissions::LOGISTIC_INVENTORY_BATCH_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_VIEW,
                Permissions::LOGISTIC_STOCK_ADJ_CREATE,
                Permissions::LOGISTIC_MR_VIEW,
                Permissions::LOGISTIC_MR_CREATE,
                Permissions::LOGISTIC_MR_EDIT,
                Permissions::LOGISTIC_MR_DELETE,
                Permissions::LOGISTIC_MR_APPROVE,
                Permissions::LOGISTIC_MR_EXPORT,
                Permissions::LOGISTIC_MATERIAL_USAGE_VIEW,
                Permissions::LOGISTIC_MATERIAL_USAGE_CREATE,
                Permissions::LOGISTIC_SHORTAGE_VIEW,
                Permissions::LOGISTIC_SHORTAGE_EDIT,
            ],

            // Timing: approve/edit semua timing data + monitor + view projects
            'timing' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::PRODUCTION_TIMING_VIEW,
                Permissions::PRODUCTION_TIMING_EDIT,
                Permissions::PRODUCTION_TIMING_APPROVE,
                Permissions::PRODUCTION_TIMING_MONITOR_VIEW,
                Permissions::PRODUCTION_MASCOT_TIMING_VIEW,
                Permissions::PRODUCTION_MASCOT_TIMING_EDIT,
                Permissions::PRODUCTION_COSTUME_TIMING_VIEW,
                Permissions::PRODUCTION_COSTUME_TIMING_EDIT,
                Permissions::PRODUCTION_ANIMATRONICS_TIMING_VIEW,
                Permissions::PRODUCTION_ANIMATRONICS_TIMING_EDIT,
                Permissions::PRODUCTION_PERFORMANCE_VIEW,
                Permissions::HR_EMPLOYEES_VIEW,
                Permissions::HR_WARNING_LETTER_VIEW,
            ],

            // General: akses terbatas — view projects & JO, monitor, ajukan leave & OT
            'general' => [
                Permissions::PRODUCTION_PROJECT_VIEW,
                Permissions::PRODUCTION_JO_VIEW,
                Permissions::PRODUCTION_TIMING_MONITOR_VIEW,
                Permissions::HR_LEAVE_VIEW,
                Permissions::HR_LEAVE_CREATE,
                Permissions::HR_OVERTIME_VIEW,
                Permissions::HR_OVERTIME_CREATE,
            ],
        ];

        foreach ($roleMap as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(array_values($permissions));
        }

        $this->command->info('RolePermissionSeeder selesai: ' . count($roleMap) . ' roles, ' . count(Permissions::all()) . ' permissions.');
    }
}
