<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Logistic\Inventory> $inventories
 * @property-read int|null $inventories_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category withoutTrashed()
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string|null $exchange_rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Logistic\Inventory> $inventories
 * @property-read int|null $inventories_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency withoutTrashed()
 */
	class Currency extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Admin\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 */
	class Department extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $employee_no
 * @property string $name
 * @property string|null $photo
 * @property string $position
 * @property int|null $department_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $rekening
 * @property \Illuminate\Support\Carbon|null $hire_date
 * @property numeric|null $salary
 * @property int $saldo_cuti
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Admin\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hr\EmployeeDocument> $documents
 * @property-read int|null $documents_count
 * @property-read mixed $employee_number_only
 * @property-read mixed $formatted_rekening
 * @property-read mixed $formatted_salary
 * @property-read mixed $photo_url
 * @property-read mixed $status_badge
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Production\Timing> $timings
 * @property-read int|null $timings_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereRekening($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSaldoCuti($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property int $employee_id
 * @property string $document_type
 * @property string $document_name
 * @property string $file_path
 * @property string|null $file_size
 * @property string|null $mime_type
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Hr\Employee $employee
 * @property-read mixed $download_filename
 * @property-read mixed $file_url
 * @property-read mixed $formatted_document_type
 * @property-read mixed $formatted_file_size
 * @property-read \App\Models\TFactory|null $use_factory
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereDocumentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeDocument whereUpdatedAt($value)
 */
	class EmployeeDocument extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property int|null $goods_out_id
 * @property int|null $inventory_id
 * @property int|null $project_id
 * @property string $quantity
 * @property string $returned_by
 * @property \Illuminate\Support\Carbon $returned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $remark
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \App\Models\Logistic\GoodsOut|null $goodsOut
 * @property-read \App\Models\Logistic\Inventory|null $inventory
 * @property-read \App\Models\Production\Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereGoodsOutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereReturnedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereReturnedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsIn withoutTrashed()
 */
	class GoodsIn extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property int|null $material_request_id
 * @property int $inventory_id
 * @property int|null $project_id
 * @property string $requested_by
 * @property string $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $remark
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $remaining_quantity
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Logistic\GoodsIn> $goodsIns
 * @property-read int|null $goods_ins_count
 * @property-read \App\Models\Logistic\Inventory $inventory
 * @property-read \App\Models\Logistic\MaterialRequest|null $materialRequest
 * @property-read \App\Models\Production\Project|null $project
 * @property-read \App\Models\Admin\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereMaterialRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GoodsOut withoutTrashed()
 */
	class GoodsOut extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string $quantity
 * @property string $unit
 * @property string|null $price
 * @property \App\Models\Procurement\Supplier|null $supplier
 * @property int|null $currency_id
 * @property int|null $supplier_id
 * @property int|null $location_id
 * @property \App\Models\Logistic\Location|null $location
 * @property string|null $remark
 * @property string|null $img
 * @property string|null $qrcode_path
 * @property string|null $qrcode
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Logistic\Category|null $category
 * @property-read \App\Models\Finance\Currency|null $currency
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Logistic\GoodsOut> $goodsOuts
 * @property-read int|null $goods_outs_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereQrcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereQrcodePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Inventory withoutTrashed()
 */
	class Inventory extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Location whereUpdatedAt($value)
 */
	class Location extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property int $inventory_id
 * @property int $project_id
 * @property string $qty
 * @property string $processed_qty
 * @property string $requested_by
 * @property string|null $remark
 * @property string $status
 * @property string|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $department_name
 * @property-read mixed $remaining_qty
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Logistic\GoodsOut> $goodsOuts
 * @property-read int|null $goods_outs_count
 * @property-read \App\Models\Logistic\Inventory $inventory
 * @property-read \App\Models\Production\Project $project
 * @property-read \App\Models\Admin\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereProcessedQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialRequest withoutTrashed()
 */
	class MaterialRequest extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property int $inventory_id
 * @property int $project_id
 * @property string $used_quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Logistic\Inventory $inventory
 * @property-read \App\Models\Production\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereInventoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage whereUsedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaterialUsage withoutTrashed()
 */
	class MaterialUsage extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $created_by
 * @property string $name
 * @property int $qty
 * @property int|null $department_id
 * @property string|null $img
 * @property string|null $start_date
 * @property string|null $deadline
 * @property string|null $finish_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Admin\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaterialUsage> $materialUsages
 * @property-read int|null $material_usages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Production\ProjectPart> $parts
 * @property-read int|null $parts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeadline($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereFinishDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 */
	class Project extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property int $project_id
 * @property string $part_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Production\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart wherePartName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectPart whereUpdatedAt($value)
 */
	class ProjectPart extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUpdatedAt($value)
 */
	class Supplier extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $tanggal
 * @property int $project_id
 * @property string $step
 * @property string|null $parts
 * @property int $employee_id
 * @property string $start_time
 * @property string $end_time
 * @property int $output_qty
 * @property string $status
 * @property string|null $remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Hr\Employee $employee
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \App\Models\Production\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereOutputQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereParts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Timing whereUpdatedAt($value)
 */
	class Timing extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TFactory|null $use_factory
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Unit whereUpdatedAt($value)
 */
	class Unit extends \Eloquent {}
}

namespace App\Models{
/**
 *
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $role
 * @property int|null $department_id
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Admin\Department|null $department
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

