import '../bootstrap';

// Entry khusus layar kiosk absensi (RFID reader / QR scanner / kamera face recognition).
// Sengaja TIDAK import Alpine.js di sini: kiosk berjalan di perangkat fisik yang mungkin
// idle berjam-jam nyala terus, jadi bundle-nya dijaga seringan mungkin dan cuma memuat
// yang benar-benar dipakai (axios untuk POST event absensi ke API, ditambah kode
// device-specific seperti akses kamera/webcam nanti di sini).
//
// Kalau modul Absensi mulai digarap dan ternyata butuh interaktivitas ringan
// (mis. animasi status "berhasil scan"), boleh tambah `import '../alpine'` di sini —
// tapi evaluasi dulu apakah vanilla JS sudah cukup sebelum nambah dependency.
