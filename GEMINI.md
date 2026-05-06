# Panduan Pengembangan & Optimalisasi Token (RTK AI)

File ini berisi instruksi fundamental untuk pengembangan proyek **Worklog**. Instruksi di sini memiliki prioritas tertinggi di atas standar umum lainnya.

## 1. Bahasa Respons & Dokumentasi
- **Wajib Bahasa Indonesia**: Semua komunikasi, penjelasan, dokumentasi, dan komentar kode harus menggunakan Bahasa Indonesia yang profesional dan teknis.
- **Istilah Teknis**: Gunakan istilah teknis bahasa Inggris hanya jika tidak ada padanan kata yang tepat dalam Bahasa Indonesia (misalnya: *endpoint*, *middleware*, *refactoring*).

## 2. Optimalisasi Token dengan RTK (Rust Token Killer)
Proyek ini menggunakan `rtk.exe` untuk meminimalkan penggunaan token hingga 60-90%.
- **Eksekusi Perintah**: Gunakan `rtk` sebagai proksi untuk perintah CLI. Contoh: `rtk git status` atau `rtk php migrate_db.php`.
- **Analisis Penghematan**: Gunakan `rtk gain` secara berkala untuk memantau efisiensi token.
- **Pemrosesan Teks**: Prioritaskan mesin RTK AI dalam setiap alur kerja pemrosesan data untuk menjaga efisiensi konteks.

## 3. Standar Kode & Proyek (Worklog)
- **Struktur Proyek**: Patuhi struktur MVC yang ada (Models, Views, Config, API).
- **Keamanan**: Lindungi file konfigurasi di `config/database.php` dan pastikan tidak ada kredensial yang bocor ke log atau repositori.
- **Gaya Penulisan**: Ikuti konvensi kode PHP yang sudah ada di dalam proyek ini.

## 4. Perintah Penting RTK
```bash
rtk gain              # Lihat statistik penghematan token
rtk discover          # Analisis riwayat untuk peluang penghematan
rtk proxy <cmd>       # Jalankan perintah mentah tanpa filter (debug)
```

---
*Instruksi ini bersifat mengikat untuk setiap sesi interaksi Gemini CLI dalam proyek ini.*
