<?php

namespace App\Support;

/**
 * Daftar permission constants untuk seluruh modul sistem.
 * Format: {modul}.{sub}.{aksi}
 *
 * Digunakan sebagai referensi di seeder, blade (@can), dan controller ($this->authorize).
 */
class Permissions
{
    // ─────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────
    const ADMIN_USERS_VIEW        = 'admin.users.view';
    const ADMIN_USERS_CREATE      = 'admin.users.create';
    const ADMIN_USERS_EDIT        = 'admin.users.edit';
    const ADMIN_USERS_DELETE      = 'admin.users.delete';

    const ADMIN_DEPARTMENTS_VIEW  = 'admin.departments.view';
    const ADMIN_DEPARTMENTS_EDIT  = 'admin.departments.edit';

    const ADMIN_HOLIDAYS_VIEW     = 'admin.holidays.view';
    const ADMIN_HOLIDAYS_EDIT     = 'admin.holidays.edit';

    const ADMIN_AUDIT_VIEW        = 'admin.audit.view';
    const ADMIN_AUDIT_DELETE      = 'admin.audit.delete';

    // ─────────────────────────────────────────────
    // HR
    // ─────────────────────────────────────────────
    const HR_DASHBOARD_VIEW            = 'hr.dashboard.view';

    const HR_EMPLOYEES_VIEW            = 'hr.employees.view';
    const HR_EMPLOYEES_CREATE          = 'hr.employees.create';
    const HR_EMPLOYEES_EDIT            = 'hr.employees.edit';
    const HR_EMPLOYEES_DELETE          = 'hr.employees.delete';
    const HR_EMPLOYEES_IMPORT          = 'hr.employees.import';

    const HR_ATTENDANCE_VIEW           = 'hr.attendance.view';
    const HR_ATTENDANCE_EDIT           = 'hr.attendance.edit';
    const HR_ATTENDANCE_EXPORT         = 'hr.attendance.export';

    const HR_FINGERSPOT_VIEW           = 'hr.fingerspot.view';
    const HR_FINGERSPOT_REGISTER       = 'hr.fingerspot.register';

    const HR_LEAVE_VIEW                = 'hr.leave.view';
    const HR_LEAVE_CREATE              = 'hr.leave.create';

    // Leave Approval Level 1 — per department (Production menu)
    const HR_LEAVE_DEPT_APPROVE_MASCOT      = 'hr.leave.dept-approve.mascot';
    const HR_LEAVE_DEPT_APPROVE_COSTUME     = 'hr.leave.dept-approve.costume'; // DCM Costume + Plush
    const HR_LEAVE_DEPT_APPROVE_ANIMATRONIC = 'hr.leave.dept-approve.animatronic';
    const HR_LEAVE_DEPT_APPROVE_LOGISTIC    = 'hr.leave.dept-approve.logistic';

    // Leave Approval Level 2 & 3 — HR & Director (HR menu)
    const HR_LEAVE_APPROVE_HR          = 'hr.leave.approve.hr';

    const HR_OVERTIME_VIEW             = 'hr.overtime.view';
    const HR_OVERTIME_CREATE           = 'hr.overtime.create';
    const HR_OVERTIME_APPROVE          = 'hr.overtime.approve';
    const HR_OVERTIME_PAY_VIEW         = 'hr.overtime-pay.view';
    const HR_OVERTIME_PAY_EDIT         = 'hr.overtime-pay.edit';

    const HR_WARNING_LETTER_VIEW       = 'hr.warning-letter.view';
    const HR_WARNING_LETTER_CREATE     = 'hr.warning-letter.create';
    const HR_WARNING_LETTER_EDIT       = 'hr.warning-letter.edit';
    const HR_WARNING_LETTER_DELETE     = 'hr.warning-letter.delete';

    const HR_WARNING_BATCH_VIEW        = 'hr.warning-batch.view';
    const HR_WARNING_BATCH_CREATE      = 'hr.warning-batch.create';

    // ─────────────────────────────────────────────
    // PRODUCTION
    // ─────────────────────────────────────────────
    const PRODUCTION_PROJECT_VIEW       = 'production.project.view';
    const PRODUCTION_PROJECT_CREATE     = 'production.project.create';
    const PRODUCTION_PROJECT_EDIT       = 'production.project.edit';
    const PRODUCTION_PROJECT_DELETE     = 'production.project.delete';

    const PRODUCTION_MATERIAL_PLANNING_VIEW   = 'production.material-planning.view';
    const PRODUCTION_MATERIAL_PLANNING_CREATE = 'production.material-planning.create';
    const PRODUCTION_MATERIAL_PLANNING_EDIT   = 'production.material-planning.edit';
    const PRODUCTION_MATERIAL_PLANNING_DELETE = 'production.material-planning.delete';

    const PRODUCTION_JO_VIEW           = 'production.jo.view';
    const PRODUCTION_JO_CREATE         = 'production.jo.create';
    const PRODUCTION_JO_EDIT           = 'production.jo.edit';
    const PRODUCTION_JO_DELETE         = 'production.jo.delete';
    const PRODUCTION_JO_EXPORT         = 'production.jo.export';

    const PRODUCTION_TIMING_VIEW       = 'production.timing.view';
    const PRODUCTION_TIMING_EDIT       = 'production.timing.edit';
    const PRODUCTION_TIMING_APPROVE    = 'production.timing.approve';

    const PRODUCTION_TIMING_MONITOR_VIEW = 'production.timing-monitor.view';

    const PRODUCTION_MASCOT_TIMING_VIEW  = 'production.mascot-timing.view';
    const PRODUCTION_MASCOT_TIMING_EDIT  = 'production.mascot-timing.edit';

    const PRODUCTION_COSTUME_TIMING_VIEW = 'production.costume-timing.view';
    const PRODUCTION_COSTUME_TIMING_EDIT = 'production.costume-timing.edit';

    const PRODUCTION_ANIMATRONICS_TIMING_VIEW = 'production.animatronics-timing.view';
    const PRODUCTION_ANIMATRONICS_TIMING_EDIT = 'production.animatronics-timing.edit';

    const PRODUCTION_CROSS_TIMING_VIEW = 'production.timing-cross.view';
    const PRODUCTION_CROSS_TIMING_EDIT = 'production.timing-cross.edit';

    const PRODUCTION_PERFORMANCE_VIEW  = 'production.performance.view';
    const PRODUCTION_EFFICIENCY_VIEW   = 'production.efficiency.view';

    // ─────────────────────────────────────────────
    // LOGISTIC
    // ─────────────────────────────────────────────
    const LOGISTIC_INVENTORY_VIEW      = 'logistic.inventory.view';
    const LOGISTIC_INVENTORY_CREATE    = 'logistic.inventory.create';
    const LOGISTIC_INVENTORY_EDIT      = 'logistic.inventory.edit';
    const LOGISTIC_INVENTORY_DELETE    = 'logistic.inventory.delete';
    const LOGISTIC_INVENTORY_EXPORT    = 'logistic.inventory.export';
    const LOGISTIC_INVENTORY_IMPORT    = 'logistic.inventory.import';

    const LOGISTIC_INVENTORY_BATCH_VIEW  = 'logistic.inventory-batch.view';

    const LOGISTIC_GOODS_IN_VIEW       = 'logistic.goods-in.view';
    const LOGISTIC_GOODS_IN_CREATE     = 'logistic.goods-in.create';
    const LOGISTIC_GOODS_IN_EDIT       = 'logistic.goods-in.edit';

    const LOGISTIC_GOODS_OUT_VIEW      = 'logistic.goods-out.view';
    const LOGISTIC_GOODS_OUT_CREATE    = 'logistic.goods-out.create';
    const LOGISTIC_GOODS_OUT_EDIT      = 'logistic.goods-out.edit';

    const LOGISTIC_STOCK_ADJ_VIEW      = 'logistic.stock-adjustment.view';
    const LOGISTIC_STOCK_ADJ_CREATE    = 'logistic.stock-adjustment.create';

    const LOGISTIC_MR_VIEW             = 'logistic.material-request.view';
    const LOGISTIC_MR_CREATE           = 'logistic.material-request.create';
    const LOGISTIC_MR_EDIT             = 'logistic.material-request.edit';
    const LOGISTIC_MR_DELETE           = 'logistic.material-request.delete';
    const LOGISTIC_MR_APPROVE          = 'logistic.material-request.approve';
    const LOGISTIC_MR_EXPORT           = 'logistic.material-request.export';

    const LOGISTIC_MATERIAL_USAGE_VIEW   = 'logistic.material-usage.view';
    const LOGISTIC_MATERIAL_USAGE_CREATE = 'logistic.material-usage.create';

    const LOGISTIC_SHORTAGE_VIEW       = 'logistic.shortage.view';
    const LOGISTIC_SHORTAGE_EDIT       = 'logistic.shortage.edit';

    // ─────────────────────────────────────────────
    // PROCUREMENT
    // ─────────────────────────────────────────────
    const PROCUREMENT_PO_VIEW          = 'procurement.po.view';
    const PROCUREMENT_PO_CREATE        = 'procurement.po.create';
    const PROCUREMENT_PO_EDIT          = 'procurement.po.edit';
    const PROCUREMENT_PO_DELETE        = 'procurement.po.delete';
    const PROCUREMENT_PO_APPROVE       = 'procurement.po.approve';

    const PROCUREMENT_SHIPPING_VIEW    = 'procurement.shipping.view';
    const PROCUREMENT_SHIPPING_EDIT    = 'procurement.shipping.edit';

    const PROCUREMENT_SUPPLIER_VIEW    = 'procurement.supplier.view';
    const PROCUREMENT_SUPPLIER_EDIT    = 'procurement.supplier.edit';

    // ─────────────────────────────────────────────
    // FINANCE
    // ─────────────────────────────────────────────
    const FINANCE_COSTING_VIEW         = 'finance.costing.view';
    const FINANCE_COSTING_EDIT         = 'finance.costing.edit';

    const FINANCE_CURRENCY_VIEW        = 'finance.currency.view';
    const FINANCE_CURRENCY_EDIT        = 'finance.currency.edit';

    const FINANCE_KASBON_VIEW          = 'finance.kasbon.view';
    const FINANCE_KASBON_APPROVE       = 'finance.kasbon.approve';

    const FINANCE_PURCHASE_EDITED_VIEW = 'finance.purchase-edited.view';
    const FINANCE_PURCHASE_EDITED_EDIT = 'finance.purchase-edited.edit';

    // ─────────────────────────────────────────────
    // LARK
    // ─────────────────────────────────────────────
    const LARK_STAGING_VIEW            = 'lark.staging.view';
    const LARK_STAGING_SYNC            = 'lark.staging.sync';
    const LARK_STAGING_APPROVE         = 'lark.staging.approve';

    // ─────────────────────────────────────────────
    // FEATURE ANNOUNCEMENTS
    // ─────────────────────────────────────────────
    const FEATURE_ANNOUNCEMENT_VIEW    = 'feature.announcement.view';
    const FEATURE_ANNOUNCEMENT_MANAGE  = 'feature.announcement.manage';

    /**
     * Kembalikan semua nilai permission sebagai flat array.
     * Dipakai di seeder untuk create() semua permission sekaligus.
     */
    public static function all(): array
    {
        $ref = new \ReflectionClass(static::class);
        return array_values($ref->getConstants());
    }
}
