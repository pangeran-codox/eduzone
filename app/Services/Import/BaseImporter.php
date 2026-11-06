<?php

namespace App\Services\Import;

use Carbon\Carbon;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Kontrak satu jenis impor (kelas, siswa, guru, ...). Dipakai dua kali untuk baris yang sama:
 * saat dry-run (validateRow saja) dan saat impor (validateRow lagi, lalu importRow), supaya hasil
 * selalu sesuai kondisi database saat itu. Lihat PERENCANAAN_IMPOR_EXCEL.md.
 */
abstract class BaseImporter
{
    abstract public function type(): string;

    abstract public function label(): string;

    abstract public function description(): string;

    /**
     * Definisi kolom template, berurutan.
     *
     * @return array<string, array{required: bool, hint: string, example: string, options?: list<string>, list?: string, width?: int}>
     */
    abstract public function columns(): array;

    /**
     * Daftar nilai untuk lembar "Daftar" di template (mis. nama kelas aktif), dirujuk lewat kunci `list` pada kolom.
     *
     * @return array<string, list<string>>
     */
    public function lists(string $schoolId): array
    {
        return [];
    }

    /**
     * Sesuaikan importer dengan sekolahnya (mis. jenjang SMA/SMK/MA) SEBELUM columns() dibaca untuk template.
     * Ringan: tidak memuat data pembanding. prepare() boleh memanggilnya.
     */
    public function configure(string $schoolId): void
    {
    }

    /** Muat data pembanding (kelas, jurusan, ...) sekali sebelum memproses banyak baris. */
    public function prepare(string $schoolId): void
    {
    }

    /**
     * Validasi satu baris.
     *
     * @param array<string, mixed> $row   nilai mentah per kunci kolom
     * @param array<string, mixed> $state status lintas-baris untuk mendeteksi duplikat di dalam file (diubah di tempat)
     * @return array{0: array<string, mixed>|null, 1: list<array{column: string, message: string}>, 2: list<string>}
     *         [data ternormalisasi (null bila ada galat), galat, peringatan]
     */
    abstract public function validateRow(array $row, array &$state): array;

    /**
     * Simpan satu baris yang sudah valid. Dipanggil di luar transaksi pemanggil: implementasi membuat transaksinya sendiri.
     *
     * @param array<string, mixed> $data hasil validateRow
     * @return array{status: 'created'|'skipped', message?: string, credential?: array<string, string>}
     */
    abstract public function importRow(array $data, string $schoolId): array;

    /**
     * Catatan ringkasan setelah semua baris divalidasi (mis. "Jurusan baru akan dibuat: ...").
     *
     * @param array<string, mixed> $state
     * @return list<string>
     */
    public function summaryNotes(array $state): array
    {
        return [];
    }

    /** Apakah impor ini menghasilkan akun login (laporan kata sandi awal)? */
    public function createsAccounts(): bool
    {
        return false;
    }

    /** @return list<string> */
    public function requiredKeys(): array
    {
        return array_keys(array_filter($this->columns(), fn (array $c) => $c['required']));
    }

    // ------------------------------------------------------------------ pembantu normalisasi

    /**
     * Jalankan $fn; kalau melempar InvalidArgumentException, catat sebagai galat kolom dan kembalikan null.
     *
     * @param list<array{column: string, message: string}> $errors
     */
    protected function attempt(array &$errors, string $column, callable $fn): mixed
    {
        try {
            return $fn();
        } catch (InvalidArgumentException $e) {
            $errors[] = ['column' => $column, 'message' => $e->getMessage()];

            return null;
        }
    }

    /** Teks dipangkas; kosong menjadi null. Angka dari sel numerik diubah ke teks tanpa notasi ilmiah. */
    protected function text(mixed $value, int $max = 255, bool $required = false): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        } elseif (is_float($value)) {
            $value = floor($value) == $value ? number_format($value, 0, '', '') : (string) $value;
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = $value === null ? '' : (string) $value;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($value === '') {
            if ($required) {
                throw new InvalidArgumentException('wajib diisi');
            }

            return null;
        }

        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException("maksimal {$max} karakter");
        }

        return $value;
    }

    /**
     * Deretan angka (NISN, NIS, NIP, NUPTK). Sel ANGKA Excel menghilangkan nol di depan: $padTo mengembalikannya (hanya untuk sel angka).
     */
    protected function digits(mixed $value, ?int $padTo = null, ?int $max = null, bool $required = false): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            if ($required) {
                throw new InvalidArgumentException('wajib diisi');
            }

            return null;
        }

        $wasNumeric = is_int($value) || is_float($value);

        if (is_float($value)) {
            if (abs($value) >= 1e15) {
                throw new InvalidArgumentException('angka terlalu panjang dan sudah kehilangan ketelitian di Excel; format kolom sebagai Teks lalu ketik ulang');
            }
            $value = number_format($value, 0, '', '');
        }

        $s = preg_replace('/\s+/', '', (string) $value) ?? '';

        if ($s === '') {
            if ($required) {
                throw new InvalidArgumentException('wajib diisi');
            }

            return null;
        }

        if (! ctype_digit($s)) {
            throw new InvalidArgumentException('hanya boleh angka (jangan memakai notasi ilmiah atau tanda baca)');
        }

        // Angka nol di depan hanya hilang bila sel disimpan sebagai ANGKA; teks yang diketik apa adanya tidak diubah.
        if ($padTo !== null && $wasNumeric && strlen($s) < $padTo) {
            $s = str_pad($s, $padTo, '0', STR_PAD_LEFT);
        }

        if ($max !== null && strlen($s) > $max) {
            throw new InvalidArgumentException("maksimal {$max} digit");
        }

        return $s;
    }

    /**
     * Tanggal dari sel Excel: nomor seri tanggal Excel, objek tanggal, atau teks YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY.
     * Mengembalikan 'Y-m-d' atau null bila kosong.
     */
    protected function date(mixed $value, bool $required = false, ?string $min = null, ?string $max = null): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            if ($required) {
                throw new InvalidArgumentException('wajib diisi');
            }

            return null;
        }

        $result = null;

        if ($value instanceof \DateTimeInterface) {
            $result = $value->format('Y-m-d');
        } elseif (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^\d{5}(\.\d+)?$/', trim($value)))) {
            $serial = (float) $value;
            if ($serial < 1 || $serial > 80000) {
                throw new InvalidArgumentException('format tanggal tidak dikenali (pakai DD/MM/YYYY atau YYYY-MM-DD)');
            }
            $result = ExcelDate::excelToDateTimeObject($serial)->format('Y-m-d');
        } else {
            $s = trim((string) $value);

            // Tahun WAJIB 4 digit: format 'Y' PHP menerima 1-4 digit, sehingga "17/05/10" akan terbaca tahun 10 Masehi.
            $patterns = [
                'Y-n-j' => '/^\d{4}-\d{1,2}-\d{1,2}$/',
                'j/n/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}$/',
                'j-n-Y' => '/^\d{1,2}-\d{1,2}-\d{4}$/',
                'j.n.Y' => '/^\d{1,2}\.\d{1,2}\.\d{4}$/',
            ];

            foreach ($patterns as $format => $pattern) {
                if (! preg_match($pattern, $s)) {
                    continue;
                }

                $dt = \DateTime::createFromFormat('!' . $format, $s);
                $errors = \DateTime::getLastErrors();
                $clean = $errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0);

                if ($dt && $clean) {
                    $result = $dt->format('Y-m-d');
                    break;
                }
            }

            if ($result === null) {
                throw new InvalidArgumentException('format tanggal tidak dikenali (pakai DD/MM/YYYY atau YYYY-MM-DD)');
            }
        }

        if ($min !== null && $result < $min) {
            throw new InvalidArgumentException('tanggal tidak masuk akal (terlalu lama): ' . Carbon::parse($result)->format('d/m/Y'));
        }

        if ($max !== null && $result > $max) {
            throw new InvalidArgumentException('tanggal tidak boleh di masa depan: ' . Carbon::parse($result)->format('d/m/Y'));
        }

        return $result;
    }

    /** L / P dari berbagai penulisan. */
    protected function gender(mixed $value, bool $required = true): ?string
    {
        $s = mb_strtolower(trim((string) ($value ?? '')));

        if ($s === '') {
            if ($required) {
                throw new InvalidArgumentException('wajib diisi (L atau P)');
            }

            return null;
        }

        return match ($s) {
            'l', 'lk', 'laki-laki', 'laki laki', 'pria', 'male', 'm' => 'L',
            'p', 'pr', 'perempuan', 'wanita', 'female', 'f' => 'P',
            default => throw new InvalidArgumentException('harus L atau P'),
        };
    }

    /** ya/tidak (juga 1/0, true/false, aktif/nonaktif). */
    protected function boolean(mixed $value, bool $default): bool
    {
        $s = mb_strtolower(trim((string) ($value ?? '')));

        if ($s === '') {
            return $default;
        }

        return match ($s) {
            'ya', 'y', 'yes', '1', 'true', 'aktif' => true,
            'tidak', 't', 'no', 'n', '0', 'false', 'nonaktif', 'tidak aktif' => false,
            default => throw new InvalidArgumentException('harus ya atau tidak'),
        };
    }

    /**
     * Pilihan dari daftar tetap, tidak peka huruf besar/kecil; mengembalikan penulisan resmi dari daftar.
     *
     * @param list<string> $options
     */
    protected function choice(mixed $value, array $options, ?string $default = null): ?string
    {
        $s = trim((string) ($value ?? ''));

        if ($s === '') {
            return $default;
        }

        foreach ($options as $option) {
            if (mb_strtolower($option) === mb_strtolower($s)) {
                return $option;
            }
        }

        throw new InvalidArgumentException('harus salah satu dari: ' . implode(', ', $options));
    }
}
