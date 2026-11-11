# Kloningan Website Undangan Pernikahan (Nadila & Hanif)

Kloningan lengkap dari website Canva:
- **URL Asli**: [https://fernandaputraperdana.my.canva.site/wedding-nadila-hanif](https://fernandaputraperdana.my.canva.site/wedding-nadila-hanif)
- **Halaman Undangan**: [https://fernandaputraperdana.my.canva.site/wedding-nadila-hanif/undangan](https://fernandaputraperdana.my.canva.site/wedding-nadila-hanif/undangan)

Semua aset (font, gambar, video MP4 & HLS playlist, stiker, skrip Canva, stylesheet, iframe form/widget RSVP, dan footer) telah diunduh dan tersimpan 100% lokal di folder `_assets/`.

---

## Struktur Direktori

```text
wedding-nadila-hanif/
│── index.html              # Halaman utama (Cover & SPA Canva)
│── undangan.html           # Halaman undangan
│── widget_1.html           # Komponen lokal widget RSVP (Bahasa Indonesia)
│── widget_2.html           # Komponen lokal widget RSVP (Bahasa Inggris)
│── _footer                 # Data footer lokal
│── server.py               # Local Web Server Python multi-threaded & support range streaming
│── start_wedding.bat       # Launcher 1-klik untuk Windows
│── README.md               # Panduan penggunaan
└── _assets/
    ├── fonts/              # Seluruh berkas WOFF font typografi
    ├── images/             # Gambar & ikon
    ├── media/              # Foto-foto mempelai & dekorasi resolusi penuh
    ├── video/              # Video mp4, gif animasi stiker, dan HLS playlist m3u
    ├── widget/             # Script & CSS lokal untuk widget formulir/RSVP
    └── *.js / *.css        # Bundle runtime Canva & stylesheet
```

---

## Cara Menjalankan di Lokal

### Opsi 1: Klik Dua Kali (Rekomendasi)
Cukup klik dua kali file **`start_wedding.bat`** (tersedia di root project maupun di dalam folder `wedding-nadila-hanif`).
Browser Anda akan otomatis terbuka ke alamat:
```
http://localhost:8000/wedding-nadila-hanif/
```

### Opsi 2: Lewat Terminal / Command Prompt
Buka terminal di folder `wedding-nadila-hanif` lalu jalankan:
```bash
python server.py
```

---

## Fitur & Navigasi Lokal
1. **Halaman Cover**: Menampilkan halaman pembuka "The Wedding of Nadila and Hanif".
2. **Tombol "Buka Undangan"**: Ketika diklik, langsung membuka halaman undangan (`/wedding-nadila-hanif/undangan`) secara mulus (SPA routing tanpa reload).
3. **Akses Langsung ke Undangan**: Anda juga dapat langsung mengakses URL `http://localhost:8000/wedding-nadila-hanif/undangan`.
4. **Media & Animasi**: Semua animasi stiker, video background, foto galeri, dan font tampil persis seperti aslinya secara offline/lokal.
