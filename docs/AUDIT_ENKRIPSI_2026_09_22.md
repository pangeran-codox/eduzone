# Audit Integrasi Laravel ↔ `encryption-engine`

**Tanggal audit:** 21-22 Sep 2026
**Auditor:** Bang Ucup + Claude, verifikasi langsung via terminal (bukan klaim di atas kertas)
**Berdasarkan:** `checklist-audit-integrasi-laravel.md` (19 Sep 2026)
**Metode:** Setiap poin di bawah ini dijalankan sungguhan — `git grep`, `docker exec`, tinker, test Artisan, PHPUnit — bukan ditebak.

> Catatan: dokumen `audit-enkripsi-integrasi.md` yang sempat beredar (mengaku dari "Kiro AI", tanggal 17 Juni 2026) terbukti fiktif — diklaim ada 3 "perbaikan" yang riwayat gitnya kosong sama sekali. Dokumen itu diabaikan sepenuhnya; semua status di bawah ini diverifikasi ulang dari nol.

---

## 1. Dependency & Konfigurasi Dasar

| Status | Item | Bukti |
|---|---|---|
| ✅ | Extension PHP `grpc` & `protobuf` aktif | `php -m` di container menampilkan keduanya |
| ✅ | `grpc/grpc` & `google/protobuf` di `require` (bukan `require-dev`) | `composer.json` baris 8-16 |
| ✅ | Autoload PSR-4 `Encryption\`/`GPBMetadata\` | Sudah aktif, terbukti dari `encryption:test` yang berhasil resolve class stub |
| ✅ | `config/encryption_service.php` lengkap | `host`, `api_key`, `tls_enabled`, `ca_cert_path` — ditambah `retry` & `circuit_breaker` di sesi ini |
| ✅ | Env var terisi | `encryption:test` berhasil konek, membuktikan semua env terisi benar |

## 2. Stub gRPC

| Status | Item | Bukti |
|---|---|---|
| ✅ | Method names benar (`Encrypt`, `Decrypt`, `BatchEncrypt`, `BatchDecrypt`, `HealthCheck`) | Dipakai persis di `EncryptionClient.php` |
| ✅ | Signature `decrypt()`: 3 param wajib + `aad` opsional | Dikonfirmasi di `EncryptionClientInterface` & dipakai konsisten di cast + trait |
| ⚠️ | Kesesuaian stub dengan `.proto` terbaru sisi Rust | Tidak bisa diverifikasi tanpa akses repo Rust — di luar cakupan audit sisi Laravel |

## 3. Autoload

| Status | Item | Bukti |
|---|---|---|
| ✅ | Tidak ada konflik namespace | Terbukti dari seluruh test yang jalan tanpa error autoload |
| ✅ | `composer dump-autoload` sudah dijalankan | Class-class ter-load dengan benar |

## 4. Pola Enkripsi — Audit Semua Model

| Pola | Kelas | Model | Status |
|---|---|---|---|
| JSON-bundle cast | `EncryptedAttribute` | `School`, `CounselingSession`, `StudentRecord`, `StudentSikap` | ❌→✅ **Diperbaiki** |
| Split-kolom cast | `EncryptedField` | — | ⚠️ Tidak dipakai model manapun (kode mati) |
| JSON-bundle trait | `EncryptsViaGrpcService` | `StudentSensitiveData`, `TeacherSensitiveData`, `StaffSensitiveData` | ✅ Benar sejak awal |

**Temuan detail:**
- ❌ `EncryptedAttribute` awalnya memanggil `new App\Services\EncryptionGrpcService()` — **class ini tidak pernah ada** (`Test-Path` → `False`). `decrypt()` juga hanya 1 parameter, bukan 3+aad. Semua 4 model yang memakainya gagal total, tapi tersembunyi karena `get()` menelan error via `catch (\Throwable)` dan mengembalikan `null` diam-diam.
- ✅ **Diperbaiki**: cast ditulis ulang memakai `EncryptionClientInterface`, 3 param + `aad` dari nama field (`$key`), konsisten dengan trait yang sudah benar. `set()` melempar `SensitiveDataEncryptionException` saat gagal (fail loud, bukan silent null).
- Grep menyeluruh (`git grep -n "EncryptedAttribute::class"`) memastikan tidak ada model lain di luar 4 yang sudah diketahui.
- Data dummy di `counseling_sessions` (10 baris), `student_records` (15), `student_sikap` (50) sempat tersimpan plaintext (karena `set()` lama selalu gagal, jadi tidak mungkin lewat cast) — sudah dienkripsi ulang dan diverifikasi.
- `schools`: 0 baris data saat ditemukan, tidak perlu migrasi data.
- ✅ AAD konsisten: `EncryptedAttribute` dan `EncryptsViaGrpcService` sama-sama pakai nama field logis sebagai AAD sejak perbaikan ini.

## 5. Penyimpanan `key_id`

| Status | Item | Bukti |
|---|---|---|
| ✅ | Semua baris terenkripsi menyimpan `key_id` | Terverifikasi di semua bundle JSON hasil test |
| ✅ | `key_id` saat ini konsisten `"default"` | Mode single-key, wajar untuk sebelum key rotation diaktifkan |

## 6. Kredensial & Keamanan

| Status | Item | Bukti |
|---|---|---|
| ✅ | `server.key` tidak ada di container Laravel | `find / -name "server.key"` hasil kosong |
| ✅ | `.env` tidak pernah ter-commit | `git log --all --full-history --oneline -- .env` hasil kosong sepanjang riwayat repo |
| ✅ | `ENCRYPTION_SERVICE_API_KEY` cocok dengan sisi Rust | Dibuktikan dari `encryption:test` yang berhasil (kalau tidak cocok akan `UNAUTHENTICATED`) |

## 7. Jaringan

| Status | Item | Bukti |
|---|---|---|
| ✅ | Jalur lokal (Docker Desktop) terverifikasi | `encryption:test` health check + round-trip berhasil |
| ⚠️ | Docker Swarm production | Belum dideploy — nama DNS service perlu diverifikasi ulang setelah deploy pertama (`docker network inspect`), di luar cakupan audit ini |

## 8. Error Handling & Resiliensi

| Status | Item | Bukti |
|---|---|---|
| ✅ | Status gRPC ditangani spesifik | `assertOk()` di `EncryptionClient.php` menangani `UNAUTHENTICATED`, `RESOURCE_EXHAUSTED`, `DEADLINE_EXCEEDED`, `NOT_FOUND`, `UNAVAILABLE`, `INVALID_ARGUMENT`, `INTERNAL` dengan pesan berbeda |
| ❌→✅ | Retry + circuit breaker | **Ditambahkan** di sesi ini: retry exponential backoff untuk status transient (`RESOURCE_EXHAUSTED`, `DEADLINE_EXCEEDED`, `UNAVAILABLE`), circuit breaker berbasis Cache setelah N kegagalan beruntun. Diverifikasi tidak mengganggu jalur sukses normal |
| ✅ | `BatchEncrypt`/`BatchDecrypt` tersedia | Diimplementasikan di `EncryptionClient`, siap dipakai untuk operasi massal |

## 9. Testing & Verifikasi Runtime

| Status | Item | Bukti |
|---|---|---|
| ✅ | `encryption:test` lolos | Health check OK, round-trip berhasil |
| ✅ | `test:cast-flow` lolos | 5/5 field `EncryptedAttribute` (setelah fix) |
| ✅ | `test:student-flow`, `test:teacher-flow`, `test:staff-flow` lolos | Encrypt, hash pencarian, decrypt, pencarian by NISN/NIP/NUPTK — semua end-to-end |
| ✅ | `test:audit-flow` lolos | Log create/update tercatat, tidak ada plaintext bocor ke log |
| ❌→✅ | Test otomatis dengan mock | **Ditambahkan**: `tests/Unit/Casts/EncryptedAttributeTest.php` (8 test) & `tests/Unit/Concerns/EncryptsViaGrpcServiceTest.php` (5 test) — total 13 test, semua lolos, mock `EncryptionClientInterface` (tidak butuh gRPC sungguhan) |
| ❌ | `test:encryption` (command lama, duplikat) | **Dihapus** — memakai class yang tidak ada, sudah digantikan `encryption:test` |

## 10. Dokumentasi

| Status | Item | Bukti |
|---|---|---|
| ✅ | `SensitiveDataEncryptionException.php` | Komentar `EncryptionGrpcService` → `EncryptionClient` |
| ✅ | `StoreSchoolRequest.php` / `UpdateSchoolRequest.php` | Komentar usang dihapus, 3 rule validasi field sensitif diaktifkan |
| ⚠️ | `SKILL.md` bagian gRPC/status field Sekolah | Kalimat pengganti sudah disiapkan, perlu ditempel manual oleh Bang Ucup |

---

## Perbaikan yang Dilakukan Sesi Ini

1. **`app/Casts/EncryptedAttribute.php`** — ditulis ulang total. Class `EncryptionGrpcService` yang tidak ada diganti `EncryptionClientInterface`, signature `decrypt()` diperbaiki jadi 3 param + `aad`, format penyimpanan diseragamkan jadi JSON bundle (sama seperti trait).
2. **Data dummy 4 tabel** — `counseling_sessions`, `student_records`, `student_sikap` (plaintext lama) dienkripsi ulang via tinker; `schools` tidak ada data lama.
3. **`app/Console/Commands/TestEncryptionCommand.php`** (`test:encryption`) — dihapus, digantikan `encryption:test` yang sudah benar.
4. **Form Sekolah** — `StoreSchoolRequest.php`, `UpdateSchoolRequest.php`, `create.blade.php`, `edit.blade.php`: 3 field (`principal_nip`, `bank_account_number`, `bank_account_name`) diaktifkan penuh dari kondisi `disabled`, termasuk memecah input gabungan "Nomor & Nama Rekening" jadi 2 field terpisah sesuai validasi backend. Diuji lewat form sungguhan di browser, terverifikasi tersimpan sebagai JSON bundle di DB.
5. **`app/Services/EncryptionClient.php`** — ditambah retry exponential backoff (status transient) dan circuit breaker berbasis Cache.
6. **`config/encryption_service.php`** — ditambah konfigurasi `retry` dan `circuit_breaker`.
7. **Test otomatis baru** — 13 test PHPUnit dengan mock, mencakup cast dan trait.
8. **Komentar usang** dirapikan di 3 file (`SensitiveDataEncryptionException.php`, `StoreSchoolRequest.php`, `UpdateSchoolRequest.php`).

## Temuan di Luar Cakupan Enkripsi (Dicatat, Belum Ditindaklanjuti)

- **`user_id` kosong di `activity_logs`** saat aksi dilakukan lewat Artisan command (`test:audit-flow`) — konsisten muncul di 2 kali run terpisah, kemungkinan user context tidak ter-pass ke logger di luar sesi HTTP. Di luar cakupan audit enkripsi ini.

## Checklist Final

```
Bagian 1 — Dependency & Config    : ✅✅✅✅✅
Bagian 2 — Stub gRPC              : ✅✅ / ⚠️1
Bagian 3 — Autoload               : ✅✅
Bagian 4 — Pola Enkripsi          : ✅ (diperbaiki) / ⚠️1 (kode mati, aman diabaikan)
Bagian 5 — key_id                 : ✅✅
Bagian 6 — Kredensial             : ✅✅✅
Bagian 7 — Jaringan               : ✅ / ⚠️1 (Swarm, belum dideploy)
Bagian 8 — Error Handling         : ✅✅✅ (2 baru ditambahkan)
Bagian 9 — Testing                : ✅✅✅✅✅✅ (2 baru ditambahkan)
Bagian 10 — Dokumentasi           : ✅✅ / ⚠️1 (SKILL.md, tinggal tempel manual)
```

**Total: 3 ❌ ditemukan, ke-3nya diperbaiki dan diverifikasi ulang di sesi ini. 3 ⚠️ tersisa, semuanya di luar cakupan yang bisa diverifikasi dari sisi Laravel saja (proto Rust, deploy Swarm yang belum terjadi, satu tempel manual SKILL.md).**
