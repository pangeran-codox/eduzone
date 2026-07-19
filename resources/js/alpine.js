import Alpine from 'alpinejs';

// Daftarkan Alpine ke window supaya bisa dipakai/didebug dari console browser,
// dan supaya plugin/component Alpine tambahan (kalau ada nanti) bisa akses instance yang sama.
window.Alpine = Alpine;

Alpine.start();
