# Instruksi Bahasa & Token untuk GEMINI.md

1. **Bahasa Respons**
   - Semua output, penjelasan, dan dokumentasi **HARUS** menggunakan **Bahasa Indonesia**.
   - Hindari penggunaan bahasa Inggris kecuali istilah teknis yang tidak memiliki padanan langsung.
   - Komentar dalam kode juga ditulis dalam Bahasa Indonesia.

2. **Efisiensi Token dengan RTK AI**
   - Gunakan **RTK AI** sebagai engine utama saat melakukan pemrosesan teks.
   - RTK AI harus diaktifkan untuk setiap permintaan agar penggunaan token lebih hemat.
   - Prioritaskan RTK AI dalam pipeline sebelum fallback ke model lain.
   - Pastikan konfigurasi RTK AI dicatat dalam audit trail agar mudah dilacak.

3. **Konsistensi**
   - Seluruh bagian dokumentasi, contoh, dan catatan tetap dalam Bahasa Indonesia.
   - Jika ada perubahan engine atau bahasa, harus disebutkan secara eksplisit di catatan revisi.

---

> Catatan: File ini dikonfigurasi untuk respons Bahasa Indonesia dan penggunaan RTK AI agar hemat token.