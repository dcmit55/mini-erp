# Production Migration Guide - Complete Database Upgrade

Panduan lengkap untuk deploy **semua pending migrations** dari development (`backup`) ke production (`symcore`).

## ⚠️ Informasi Kritis

- **Database Development**: `backup` (sudah dimigrate)
- **Database Production**: `symcore` (target migration)
- **Total Pending Migrations**: 5 migrations
- **Tabel yang Terpengaruh**: `inventories`, `projects`
- **Estimasi Downtime**: 30-45 menit

---

## 📊 Ringkasan Pending Migrations

| # | File Migration | Tabel | Jenis | Deskripsi |
|---|---------------|-------|-------|-----------|
| 1 | `2026_01_27_163205_add_unit_id_to_inventories_table.php` | `inventories` | Data Migration | Convert `unit` text → `unit_id` FK |
| 2 | `2026_01_28_162636_add_sales_to_projects_table.php` | `projects` | Add Column | Tambah kolom `sales` (VARCHAR) |
| 3 | `2026_01_30_100000_add_department_and_project_status_to_projects_table.php` | `projects` | Add Columns | Tambah `department`, `project_status`, `img`, `deadline` |
| 4 | `2026_01_30_153720_add_department_and_project_status_to_projects_table.php` | `projects` | Duplicate | ⚠️ Sama dengan #3 - Skip saja |
| 5 | `2026_01_31_120000_change_department_to_department_id_in_projects.php` | `projects` | Data Migration | Convert `department` → `type_dept` + `department_id` FK |

---

## ✅ Prerequisites

1. [ ] Backup database production (WAJIB!)
2. [ ] Jadwalkan maintenance window (30-45 menit)
3. [ ] Pastikan tabel reference exist: `units`, `departments`
4. [ ] Update kode Laravel untuk struktur baru
5. [ ] Test di database clone dulu (highly recommended)

---

# BAGIAN 1: BACKUP & PERSIAPAN

## Step 1.1: Backup Production Database

```bash
# Backup full database
mysqldump -u root -p symcore > symcore_backup_$(date +%Y%m%d_%H%M%S).sql

# Verify file size (harus > 0 bytes)
ls -lh symcore_backup_*.sql

# Optional: Compress untuk hemat space
gzip symcore_backup_*.sql
```

## Step 1.2: Cek Migration Status Saat Ini

```sql
USE symcore;

-- Cek migration apa saja yang sudah jalan
SELECT migration, batch 
FROM migrations 
WHERE migration LIKE '%2026%' 
ORDER BY migration;

-- Harusnya masih kosong atau belum ada yang 2026
```

## Step 1.3: Verify Reference Tables

```sql
USE symcore;

-- Cek tabel units ada dan berisi data
SELECT COUNT(*) as total_units FROM units;
SELECT id, name FROM units ORDER BY name;

-- Cek tabel departments ada dan berisi data
SELECT COUNT(*) as total_depts FROM departments;
SELECT id, name FROM departments ORDER BY name;

-- ⚠️ Jika kosong, harus diisi dulu sebelum migrate!
```

---

# BAGIAN 2: INVENTORIES MIGRATION (unit → unit_id)

**File Migration**: `2026_01_27_163205_add_unit_id_to_inventories_table.php`

## Step 2.1: Tambah Kolom unit_id

```sql
USE symcore;

-- Tambah kolom baru (nullable dulu)
ALTER TABLE inventories 
ADD COLUMN unit_id BIGINT UNSIGNED NULL AFTER unit;

-- Tambah index untuk performance
ALTER TABLE inventories 
ADD INDEX idx_unit_id (unit_id);

-- Verify
SHOW COLUMNS FROM inventories WHERE Field LIKE 'unit%';
-- Harusnya ada: unit (varchar), unit_id (bigint)
```

## Step 2.2: Migrate Data (unit text → unit_id)

```sql
USE symcore;

-- PREVIEW dulu: Lihat apa yang akan dimigrate
SELECT 
    i.id, 
    i.name, 
    i.unit as old_unit, 
    u.id as new_unit_id,
    u.name as unit_name
FROM inventories i
LEFT JOIN units u ON LOWER(TRIM(i.unit)) = LOWER(u.name)
WHERE i.unit IS NOT NULL AND i.unit != ''
LIMIT 20;

-- Kalau OK, EXECUTE:
UPDATE inventories i
INNER JOIN units u ON LOWER(TRIM(i.unit)) = LOWER(u.name)
SET i.unit_id = u.id
WHERE i.unit IS NOT NULL AND i.unit != '';

-- Cek hasil
SELECT 
    COUNT(*) as berhasil_migrate,
    (SELECT COUNT(*) FROM inventories WHERE unit IS NOT NULL AND unit != '') as total_punya_unit
FROM inventories 
WHERE unit_id IS NOT NULL;

-- Harus sama atau hampir sama (yang beda = data invalid)
```

## Step 2.3: Handle Data Invalid (unit tidak ada di tabel units)

```sql
USE symcore;

-- Cari inventory dengan unit tapi tidak dapat unit_id
SELECT id, name, unit, unit_id 
FROM inventories 
WHERE unit IS NOT NULL 
  AND unit != '' 
  AND unit_id IS NULL
ORDER BY unit;

-- Kalau ada, ada 2 opsi:

-- OPSI 1: Set NULL untuk data invalid
UPDATE inventories 
SET unit = NULL 
WHERE unit IS NOT NULL 
  AND unit != '' 
  AND unit_id IS NULL;

-- OPSI 2: Buat unit baru dulu (kalau memang valid tapi belum ada)
-- INSERT INTO units (name, created_at, updated_at) 
-- VALUES ('Nama Unit Baru', NOW(), NOW());
-- Lalu ulangi Step 2.2
```

## Step 2.4: Tambah Foreign Key Constraint

```sql
USE symcore;

-- Tambah FK constraint
ALTER TABLE inventories 
ADD CONSTRAINT inventories_unit_id_foreign 
FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL;

-- Verify constraint ada
SHOW CREATE TABLE inventories;
-- Harusnya ada: CONSTRAINT `inventories_unit_id_foreign` FOREIGN KEY...
```

## Step 2.5: Drop Kolom Lama (OPSIONAL - Untuk Cleanup)

⚠️ **Rekomendasi**: Jangan drop dulu kolom `unit`. Keep untuk audit trail. Bisa drop nanti setelah 100% yakin OK.

```sql
USE symcore;

-- JANGAN JALANKAN DULU - Keep untuk audit
-- ALTER TABLE inventories DROP COLUMN unit;

-- Cukup pastikan aplikasi pakai unit_id, bukan unit
```

## Step 2.6: Mark Migration Sebagai Complete

```sql
USE symcore;

-- Insert ke tabel migrations
INSERT INTO migrations (migration, batch) 
VALUES ('2026_01_27_163205_add_unit_id_to_inventories_table', 
        (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) as m));

-- Verify
SELECT * FROM migrations WHERE migration LIKE '%unit_id%';
```

---

# BAGIAN 3: PROJECTS - TAMBAH KOLOM BARU

**File Migration**: 
- `2026_01_28_162636_add_sales_to_projects_table.php`
- `2026_01_30_100000_add_department_and_project_status_to_projects_table.php`

## Step 3.1: Tambah Kolom sales

```sql
USE symcore;

-- Tambah kolom sales
ALTER TABLE projects 
ADD COLUMN sales VARCHAR(255) NULL AFTER name;

-- Verify
SHOW COLUMNS FROM projects LIKE 'sales';

-- Mark migration complete
INSERT INTO migrations (migration, batch) 
VALUES ('2026_01_28_162636_add_sales_to_projects_table', 
        (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) as m));
```

## Step 3.2: Tambah Kolom Lark Sync (department, project_status, img, deadline)

```sql
USE symcore;

-- Tambah department (nanti akan direname jadi type_dept)
ALTER TABLE projects 
ADD COLUMN department VARCHAR(255) NULL AFTER sales;

-- Tambah project_status
ALTER TABLE projects 
ADD COLUMN project_status TEXT NULL AFTER department;

-- Tambah img (image URLs dari Lark)
ALTER TABLE projects 
ADD COLUMN img VARCHAR(255) NULL AFTER project_status;

-- Tambah deadline
ALTER TABLE projects 
ADD COLUMN deadline DATE NULL AFTER img;

-- Verify semua kolom ada
SHOW COLUMNS FROM projects WHERE Field IN ('sales', 'department', 'project_status', 'img', 'deadline');

-- Mark migration complete
INSERT INTO migrations (migration, batch) 
VALUES ('2026_01_30_100000_add_department_and_project_status_to_projects_table', 
        (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) as m));

-- Skip duplicate migration (sama persis dengan yang di atas)
INSERT INTO migrations (migration, batch) 
VALUES ('2026_01_30_153720_add_department_and_project_status_to_projects_table', 
        (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) as m));
```

---

# BAGIAN 4: PROJECTS - DEPARTMENT MIGRATION (department → type_dept + department_id)

**File Migration**: `2026_01_31_120000_change_department_to_department_id_in_projects.php`

## Step 4.1: Tambah Kolom department_id

```sql
USE symcore;

-- Tambah kolom untuk FK relation
ALTER TABLE projects 
ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER department;

-- Tambah index
ALTER TABLE projects 
ADD INDEX idx_department_id (department_id);

-- Verify
SHOW COLUMNS FROM projects WHERE Field LIKE '%depart%';
-- Harusnya ada: department (varchar), department_id (bigint)
```

## Step 4.2: Migrate Data (department text → department_id)

```sql
USE symcore;

-- PREVIEW: Lihat yang akan dimigrate
SELECT 
    p.id,
    p.name,
    p.department as old_dept_text,
    d.id as new_dept_id,
    d.name as dept_name
FROM projects p
LEFT JOIN departments d ON LOWER(TRIM(SUBSTRING_INDEX(p.department, ',', 1))) = LOWER(d.name)
WHERE p.department IS NOT NULL AND p.department != ''
LIMIT 20;

-- EXECUTE: Ambil department pertama dari comma-separated list
UPDATE projects p
INNER JOIN departments d ON LOWER(TRIM(SUBSTRING_INDEX(p.department, ',', 1))) = LOWER(d.name)
SET p.department_id = d.id
WHERE p.department IS NOT NULL AND p.department != '';

-- Cek hasil
SELECT 
    COUNT(*) as berhasil_migrate,
    (SELECT COUNT(*) FROM projects WHERE department IS NOT NULL AND department != '') as total_punya_dept
FROM projects 
WHERE department_id IS NOT NULL;
```

## Step 4.3: Handle Multi-Department Projects (Pivot Table)

```sql
USE symcore;

-- Cari projects dengan multiple departments (ada koma)
SELECT id, name, department 
FROM projects 
WHERE department LIKE '%,%'
ORDER BY id;

-- Untuk multi-department, populate tabel pivot department_project
-- CONTOH untuk project id=123 dengan "Planning, Production":

-- INSERT INTO department_project (project_id, department_id, created_at, updated_at) 
-- SELECT 123, id, NOW(), NOW()
-- FROM departments 
-- WHERE name IN ('Planning', 'Production');

-- ⚠️ Ini manual - sesuaikan dengan data aktual
```

## Step 4.4: Rename department → type_dept

⚠️ **PENTING**: Rename untuk hindari conflict dengan method `department()` di model.

```sql
USE symcore;

-- Rename kolom
ALTER TABLE projects 
CHANGE COLUMN department type_dept VARCHAR(255) NULL;

-- Verify
SHOW COLUMNS FROM projects WHERE Field LIKE '%dept%';
-- Harusnya ada: type_dept (varchar), department_id (bigint)
```

## Step 4.5: Tambah Foreign Key Constraint

```sql
USE symcore;

-- Tambah FK constraint
ALTER TABLE projects 
ADD CONSTRAINT projects_department_id_foreign 
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Verify
SHOW CREATE TABLE projects;
-- Harusnya ada: CONSTRAINT `projects_department_id_foreign` FOREIGN KEY...
```

## Step 4.6: Mark Migration Complete

```sql
USE symcore;

-- Insert migration record
INSERT INTO migrations (migration, batch) 
VALUES ('2026_01_31_120000_change_department_to_department_id_in_projects', 
        (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) as m));

-- Verify semua 2026 migrations
SELECT migration, batch 
FROM migrations 
WHERE migration LIKE '%2026%' 
ORDER BY migration;
-- Harusnya ada 5 migrations
```

---

# BAGIAN 5: UPDATE APLIKASI LARAVEL

## Step 5.1: Clear All Caches

```bash
# Masuk ke direktori aplikasi
cd /path/to/isyment/production

# Clear config cache
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Clear compiled views
php artisan view:clear

# Clear route cache
php artisan route:clear

# Rebuild class loader
composer dump-autoload --optimize
```

## Step 5.2: Restart Services

```bash
# Restart PHP-FPM (sesuaikan versi PHP)
sudo systemctl restart php8.2-fpm

# Restart Nginx
sudo systemctl restart nginx

# ATAU jika pakai Apache:
# sudo systemctl restart apache2

# Restart queue workers (kalau pakai)
php artisan queue:restart
```

---

# BAGIAN 6: VERIFIKASI & TESTING

## Step 6.1: Verifikasi Database

```sql
USE symcore;

-- Cek semua migrations tercatat
SELECT migration, batch 
FROM migrations 
WHERE migration LIKE '%2026%' 
ORDER BY migration;
-- Harusnya ada 5 migrations

-- Verify inventories.unit_id terisi
SELECT i.id, i.name, i.unit_id, u.name as unit_name
FROM inventories i
LEFT JOIN units u ON i.unit_id = u.id
WHERE i.deleted_at IS NULL
LIMIT 10;

-- Verify projects structure
SHOW COLUMNS FROM projects WHERE Field IN ('sales', 'type_dept', 'department_id', 'project_status', 'img', 'deadline');

-- Verify projects.department_id terisi
SELECT p.id, p.name, p.type_dept, p.department_id, d.name as dept_name
FROM projects p
LEFT JOIN departments d ON p.department_id = d.id
WHERE p.deleted_at IS NULL
LIMIT 10;
```

## Step 6.2: Test Aplikasi

### Test Inventory Module
- [ ] Akses `/inventories` - harus load tanpa error
- [ ] Create inventory baru - dropdown unit harus muncul
- [ ] Edit inventory - unit harus tampil benar
- [ ] Cek Material Request form - units harus ada

### Test Projects Module
- [ ] Akses `/production/projects` - harus tampil kolom Sales, Department
- [ ] Department harus tampil nama (bukan ID)
- [ ] Create project baru - dropdown department harus ada
- [ ] Edit project - semua field baru bisa disave

### Test Lark Sync (jika ada)
- [ ] Run: `php artisan lark:sync-projects`
- [ ] Cek logs tidak ada error
- [ ] Field sales, project_status, img, deadline harus terisi

## Step 6.3: Monitor Logs

```bash
# Laravel logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Nginx error logs
tail -f /var/log/nginx/error.log

# PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

---

# BAGIAN 7: ROLLBACK (DARURAT)

Jika terjadi masalah kritis:

## Step 7.1: Stop Aplikasi

```bash
# Stop web server
sudo systemctl stop nginx

# ATAU maintenance mode
php artisan down --message="Database maintenance"
```

## Step 7.2: Restore Database

```bash
# Restore dari backup
mysql -u root -p symcore < symcore_backup_YYYYMMDD_HHMMSS.sql

# Jika file compressed:
gunzip < symcore_backup_YYYYMMDD_HHMMSS.sql.gz | mysql -u root -p symcore
```

## Step 7.3: Clear Migration Records

```sql
USE symcore;

-- Hapus migration records yang baru ditambahkan
DELETE FROM migrations 
WHERE migration IN (
    '2026_01_27_163205_add_unit_id_to_inventories_table',
    '2026_01_28_162636_add_sales_to_projects_table',
    '2026_01_30_100000_add_department_and_project_status_to_projects_table',
    '2026_01_30_153720_add_department_and_project_status_to_projects_table',
    '2026_01_31_120000_change_department_to_department_id_in_projects'
);
```

## Step 7.4: Restart Aplikasi

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear

# Start aplikasi
php artisan up

# ATAU restart web server
sudo systemctl start nginx
```

---

# BAGIAN 8: POST-MIGRATION CHECKLIST

## Database
- [ ] Semua 5 migrations ada di tabel `migrations`
- [ ] `inventories.unit_id` terisi dengan benar
- [ ] FK constraint `inventories_unit_id_foreign` ada
- [ ] `projects.sales` column ada
- [ ] `projects.type_dept` column ada (renamed dari `department`)
- [ ] `projects.department_id` column ada
- [ ] FK constraint `projects_department_id_foreign` ada
- [ ] `projects.project_status`, `img`, `deadline` columns ada
- [ ] Tidak ada orphaned records (semua FK valid)

## Aplikasi
- [ ] Inventory index page load OK
- [ ] Projects index tampil kolom Sales dan Department
- [ ] Department tampil nama (dari relation), bukan ID
- [ ] Form Create/Edit inventory berfungsi
- [ ] Form Create/Edit project berfungsi
- [ ] Dropdown Unit dan Department terisi
- [ ] Lark sync berfungsi (kalau ada)
- [ ] Tidak ada error di Laravel logs
- [ ] Tidak ada error di web server logs

## Backup & Recovery
- [ ] Backup production tersimpan dan accessible
- [ ] Backup sudah ditest (bisa direstore)
- [ ] Prosedur rollback didokumentasi
- [ ] Backup disimpan minimal 30 hari

## Performance
- [ ] Page load time masih OK
- [ ] Tidak ada N+1 query issues
- [ ] Query database optimal (cek pakai Debugbar)
- [ ] Monitor aplikasi 24-48 jam setelah migration

---

# Timeline Estimasi

| Step | Task | Waktu |
|------|------|-------|
| 1 | Backup & Persiapan | 5 menit |
| 2 | Inventories Migration | 10 menit |
| 3 | Projects - Add Columns | 5 menit |
| 4 | Projects - Department Migration | 10 menit |
| 5 | Laravel Update | 3 menit |
| 6 | Verifikasi & Testing | 10 menit |
| **TOTAL** | **Estimated Downtime** | **30-45 menit** |

---

# Troubleshooting

## Issue: "Foreign key constraint fails"
**Solusi**: Cek semua ID ada di tabel reference

```sql
-- Cari unit_id yang invalid
SELECT * FROM inventories 
WHERE unit_id IS NOT NULL 
  AND unit_id NOT IN (SELECT id FROM units);

-- Cari department_id yang invalid
SELECT * FROM projects 
WHERE department_id IS NOT NULL 
  AND department_id NOT IN (SELECT id FROM departments);
```

## Issue: "Duplicate column name"
**Solusi**: Kolom sudah ada, skip ALTER TABLE atau drop dulu

## Issue: "Laravel masih tampil struktur lama"
**Solusi**: Clear semua caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## Issue: "Relation tidak berfungsi"
**Solusi**: Verify model code pakai FK yang benar

- `Inventory` model: `unit()` relation pakai `unit_id`
- `Project` model: `department()` relation pakai `department_id`

---

## Kontak Support

- **Database Admin**: [Your DBA contact]
- **Laravel Developer**: [Your dev contact]
- **Emergency**: [Emergency hotline]

---

**Last Updated**: 2026-01-31  
**Migration Version**: Production v1.0  
**Database Target**: symcore (Production)
