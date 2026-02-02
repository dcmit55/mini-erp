# Migration Guide: Unit to Unit_ID (Database Production - symcore)

## 📋 Overview
Memindahkan data dari kolom `unit` (VARCHAR - text) menjadi `unit_id` (BIGINT - foreign key) di tabel `inventories` pada database production **symcore**.

## ⚠️ PENTING - Backup Dulu!
```bash
# Backup database production sebelum migration
mysqldump -u root symcore > backup_symcore_before_unit_migration_$(date +%Y%m%d_%H%M%S).sql
```

---

## 🔍 Step 1: Cek Kondisi Saat Ini
<!-- 
### 1.1 Cek struktur tabel inventories// -->
```bash
mysql -u root symcore -e "DESCRIBE inventories;"
```

**Yang harus ada:**
- ✅ Kolom `unit` (varchar)
- ✅ Kolom `unit_id` (bigint unsigned nullable)

### 1.2 Cek data di tabel units
```bash
mysql -u root symcore -e "SELECT id, name FROM units ORDER BY name;"
```
**Catat semua units yang ada untuk memastikan mapping lengkap**

### 1.3 Cek unique unit values di inventories
```bash
mysql -u root symcore -e "SELECT DISTINCT unit FROM inventories WHERE unit IS NOT NULL ORDER BY unit;"
```
**Pastikan semua nilai `unit` ada di tabel `units.name`**

---

## 🚀 Step 2: Migrate Data (unit → unit_id)

<!-- ### 2.1 Update unit_id berdasarkan mapping unit name -->
```bash
mysql -u root symcore -e "
UPDATE inventories i 
INNER JOIN units u ON LOWER(TRIM(i.unit)) = LOWER(u.name) 
SET i.unit_id = u.id 
WHERE i.deleted_at IS NULL AND i.unit IS NOT NULL;
"
```
<!-- 
### 2.2 Verifikasi hasil migration -->
```bash
mysql -u root symcore -e "
SELECT 
    COUNT(*) as total_inventories,
    COUNT(unit) as with_unit_text,
    COUNT(unit_id) as with_unit_id,
    ROUND(COUNT(unit_id) / COUNT(*) * 100, 2) as success_rate
FROM inventories 
WHERE deleted_at IS NULL;
"
```

**Expected Output:**
```
total_inventories | with_unit_text | with_unit_id | success_rate
       500        |      450       |     450      |    90.00
```

### 2.3 Cek data yang tidak ter-migrate (jika ada)
```bash
mysql -u root symcore -e "
SELECT id, name, unit, unit_id 
FROM inventories 
WHERE unit IS NOT NULL AND unit_id IS NULL AND deleted_at IS NULL
LIMIT 10;
"
```

**Jika ada yang tidak match:** Periksa typo atau unit yang belum ada di tabel `units`

---

## 🔧 Step 3: Cleanup Invalid unit_id (Jika Ada)

Jika ada `unit_id` yang tidak valid (tidak ada di tabel units):

```bash
mysql -u root symcore -e "
UPDATE inventories i 
LEFT JOIN units u ON i.unit_id = u.id 
SET i.unit_id = NULL 
WHERE i.unit_id IS NOT NULL AND u.id IS NULL;
"
```

---

## 🔐 Step 4: Tambahkan Foreign Key Constraint

```bash
mysql -u root symcore -e "
ALTER TABLE inventories 
ADD CONSTRAINT inventories_unit_id_foreign 
FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL;
"
```

**Jika error "constraint already exists":**
```bash
# Check existing constraints
mysql -u root symcore -e "
SELECT CONSTRAINT_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'symcore' 
AND TABLE_NAME = 'inventories' 
AND COLUMN_NAME = 'unit_id';
"
```

---

## 🗑️ Step 5: Drop Kolom unit (Text) - OPTIONAL

**⚠️ HATI-HATI! Pastikan semua data sudah ter-migrate dengan benar**

```bash
# Verifikasi final sebelum drop
mysql -u root symcore -e "
SELECT 
    'Ready to drop' as status,
    COUNT(*) as total,
    COUNT(unit_id) as migrated,
    COUNT(unit) - COUNT(unit_id) as remaining
FROM inventories 
WHERE deleted_at IS NULL;
"
```

**Jika remaining = 0 atau acceptable:**
```bash
mysql -u root symcore -e "ALTER TABLE inventories DROP COLUMN unit;"
```

---

## 📝 Step 6: Update Aplikasi Laravel

### 6.1 Update Model Inventory
File: `app/Models/Logistic/Inventory.php`

**Pastikan:**
```php
protected $fillable = [
    // ... other fields
    'unit_id', // Sudah ada, bukan 'unit'
];

// Relasi harus ada
public function unit()
{
    return $this->belongsTo(Unit::class, 'unit_id');
}
```

### 6.2 Mark Migration sebagai Completed
```bash
cd /path/to/project
mysql -u root symcore -e "
INSERT INTO migrations (migration, batch) 
VALUES ('2024_xx_xx_xxxxxx_change_unit_to_unit_id_in_inventories', 
        (SELECT IFNULL(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)
);
"
```

### 6.3 Clear Cache
```bash
php artisan optimize:clear
```

---

## 🧪 Step 7: Testing

### 7.1 Test via Tinker
```bash
php artisan tinker --execute="
\$inventory = App\Models\Logistic\Inventory::whereNotNull('unit_id')->first();
echo 'Inventory: ' . \$inventory->name . PHP_EOL;
echo 'Unit ID: ' . \$inventory->unit_id . PHP_EOL;
echo 'Unit Name (relation): ' . \$inventory->unit->name . PHP_EOL;
"
```

### 7.2 Test di Browser
1. Buka halaman **Inventory Index**
2. Cek kolom Unit tampil dengan benar
3. Cek filter by unit masih berfungsi
4. Test create/edit inventory

---

## 🗂️ Step 8: Cleanup Migration Files (Opsional)

### Hapus migration files yang sudah tidak relevan:

```bash
cd database/migrations

# List migrations terkait unit
ls -la *unit*

# Hapus migration lama yang sudah tidak dipakai (jika ada duplikat)
# HATI-HATI: Hanya hapus jika sudah yakin tidak diperlukan
rm 2024_xx_xx_xxxxxx_old_unit_migration.php
```

**Atau via git:**
```bash
git rm database/migrations/2024_xx_xx_xxxxxx_old_unit_migration.php
git commit -m "Remove obsolete unit migration"
```

---

## ✅ Checklist Final

- [ ] ✅ Database backup created
- [ ] ✅ Data migrated from `unit` to `unit_id`
- [ ] ✅ Invalid `unit_id` cleaned up
- [ ] ✅ Foreign key constraint added
- [ ] ✅ Old `unit` column dropped (optional)
- [ ] ✅ Model updated (`$fillable`, relasi)
- [ ] ✅ Migration marked as completed
- [ ] ✅ Cache cleared
- [ ] ✅ Testing passed
- [ ] ✅ Obsolete migration files removed

---

## 🆘 Troubleshooting

### Error: "Cannot add foreign key constraint"
**Penyebab:** Ada `unit_id` yang tidak valid
**Solusi:** Jalankan Step 3 (Cleanup Invalid unit_id)

### Error: "Column 'unit' doesn't exist" di aplikasi
**Penyebab:** Aplikasi masih reference kolom `unit` yang sudah di-drop
**Solusi:** 
1. Cek views: `grep -r "->unit" resources/views/logistic/inventories/`
2. Cek controller: `grep -r "request('unit')" app/Http/Controllers/`
3. Update ke `->unit->name` atau `unit_id`

### Data tidak ter-migrate semua
**Penyebab:** Typo atau case sensitivity
**Solusi:**
```bash
# Cek unit yang tidak match
mysql -u root symcore -e "
SELECT DISTINCT i.unit, 'Not found in units table' as issue
FROM inventories i
LEFT JOIN units u ON LOWER(TRIM(i.unit)) = LOWER(u.name)
WHERE i.unit IS NOT NULL AND u.id IS NULL;
"
```

---

<!-- ## 📞 Kontak -->
Jika ada masalah saat migration, backup database ada di:
```
backup_symcore_before_unit_migration_YYYYMMDD_HHMMSS.sql
```

Restore jika perlu:
```bash
mysql -u root symcore < backup_symcore_before_unit_migration_YYYYMMDD_HHMMSS.sql
```
