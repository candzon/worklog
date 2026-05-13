<?php
/**
 * migrate_db.php
 * Skrip untuk membersihkan dan menstandarisasi struktur database
 */
require_once 'config/database.php';

$steps = [
    // 1. Perbaiki Tabel Employee
    "ALTER TABLE employee CHANGE COLUMN nama_bagian bagian_id INT NOT NULL",
    "ALTER TABLE employee MODIFY COLUMN npp VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL",
    "ALTER TABLE employee ADD CONSTRAINT fk_emp_bagian FOREIGN KEY (bagian_id) REFERENCES bagian(id_bagian) ON UPDATE CASCADE",

    // 2. Perbaiki Tabel Master Tugas
    "ALTER TABLE master_tugas DROP FOREIGN KEY master_tugas_ibfk_2", // Hapus FK ke npp lama
    "ALTER TABLE master_tugas DROP COLUMN npp", // Hapus kolom npp yang tidak digunakan lagi
    "ALTER TABLE master_tugas MODIFY COLUMN judul VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL",
    "ALTER TABLE master_tugas ADD COLUMN updated_at DATETIME NULL AFTER created_at",

    // 3. Perbaiki Tabel Pekerjaan
    "ALTER TABLE pekerjaan DROP COLUMN nama_emp", // Redundan (bisa join dari employee)
    "ALTER TABLE pekerjaan DROP COLUMN bagian_penerima", // Obsolete (sudah pakai detail)
    "ALTER TABLE pekerjaan MODIFY COLUMN status ENUM('open', 'done') DEFAULT 'open'",
    "ALTER TABLE pekerjaan MODIFY COLUMN periode ENUM('manual', 'bulanan', 'harian') DEFAULT 'manual'",

    // 4. Sinkronisasi Collation agar konsisten (utf8mb4)
    "ALTER TABLE employee CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE pekerjaan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE master_tugas_detail CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
];

echo "Starting Migration...\n";
foreach ($steps as $sql) {
    try {
        if ($conn->query($sql)) {
            echo "SUCCESS: $sql\n";
        }
    } catch (Exception $e) {
        echo "SKIPPED/ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Migration Finished.\n";
?>