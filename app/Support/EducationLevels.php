<?php

namespace App\Support;

/**
 * Satu-satunya tempat yang mengetahui perbedaan antar jenjang: daftar kode jenjang, tingkat kelas yang sah, dan istilah jurusan.
 * Menambah jenjang (mis. SLB) = menambah satu entri di profiles() dan satu kode di CODES.
 *
 * Dipakai oleh: importer Kelas (validasi tingkat, istilah/kewajiban jurusan) dan daftar jenjang pada form pendaftaran/sekolah
 * lewat `SchoolController::LEVELS = EducationLevels::CODES`.
 *
 * Tingkat disimpan dalam angka Romawi baku (X, XI, XII), sesuai data seeder (`classes.grade = 'X'`). Masukan angka ("10") diterima dan diubah.
 */
final class EducationLevels
{
    /** Kode yang disimpan di `schools.level`. */
    public const CODES = ['SD', 'MI', 'SMP', 'MTs', 'SMA', 'SMK', 'MA', 'MAK'];

    private const ROMAN = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII',
        8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 13 => 'XIII',
    ];

    /**
     * @return array<string, array{
     *     name: string,
     *     grades: list<string>,
     *     major_label: string|null,
     *     major_required: bool,
     *     major_examples: list<string>
     * }>
     *  major_label null = jenjang tanpa jurusan (kolom jurusan dikosongkan; otomatis "Umum").
     */
    public static function profiles(): array
    {
        $sd = ['I', 'II', 'III', 'IV', 'V', 'VI'];
        $smp = ['VII', 'VIII', 'IX'];
        $sma = ['X', 'XI', 'XII'];
        $smk = ['X', 'XI', 'XII', 'XIII']; // XIII untuk program keahlian 4 tahun

        return [
            'SD' => ['name' => 'Sekolah Dasar', 'grades' => $sd, 'major_label' => null, 'major_required' => false, 'major_examples' => []],
            'MI' => ['name' => 'Madrasah Ibtidaiyah', 'grades' => $sd, 'major_label' => null, 'major_required' => false, 'major_examples' => []],
            'SMP' => ['name' => 'Sekolah Menengah Pertama', 'grades' => $smp, 'major_label' => null, 'major_required' => false, 'major_examples' => []],
            'MTs' => ['name' => 'Madrasah Tsanawiyah', 'grades' => $smp, 'major_label' => null, 'major_required' => false, 'major_examples' => []],
            'SMA' => ['name' => 'Sekolah Menengah Atas', 'grades' => $sma, 'major_label' => 'Jurusan/peminatan', 'major_required' => false, 'major_examples' => ['IPA', 'IPS', 'Bahasa']],
            'SMK' => ['name' => 'Sekolah Menengah Kejuruan', 'grades' => $smk, 'major_label' => 'Kompetensi keahlian', 'major_required' => true, 'major_examples' => ['TKJ', 'RPL', 'Akuntansi dan Keuangan Lembaga']],
            'MA' => ['name' => 'Madrasah Aliyah', 'grades' => $sma, 'major_label' => 'Jurusan/peminatan', 'major_required' => false, 'major_examples' => ['IPA', 'IPS', 'Bahasa', 'Keagamaan']],
            'MAK' => ['name' => 'Madrasah Aliyah Kejuruan', 'grades' => $smk, 'major_label' => 'Kompetensi keahlian', 'major_required' => true, 'major_examples' => ['TKJ', 'Tata Busana']],
        ];
    }

    /** Profil untuk kode jenjang (tidak peka huruf besar/kecil), atau null bila kosong/tidak dikenal. */
    public static function profile(?string $code): ?array
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        foreach (self::profiles() as $key => $profile) {
            if (mb_strtolower($key) === mb_strtolower(trim($code))) {
                return $profile;
            }
        }

        return null;
    }

    /** Nama baku kode jenjang sebagaimana tersimpan di CODES (mis. "mts" menjadi "MTs"), atau null. */
    public static function canonicalCode(?string $code): ?string
    {
        foreach (self::CODES as $known) {
            if ($code !== null && mb_strtolower($known) === mb_strtolower(trim($code))) {
                return $known;
            }
        }

        return null;
    }

    /**
     * Ubah penulisan tingkat menjadi angka Romawi baku: "10", "x", "Kelas 11", "xii" -> "X", "X", "XI", "XII".
     * Mengembalikan null bila tidak dikenali (di luar 1 sampai 13).
     */
    public static function normalizeGrade(mixed $raw): ?string
    {
        $s = mb_strtoupper(trim((string) ($raw ?? '')));
        $s = trim(preg_replace('/^KELAS\s*/u', '', $s) ?? '');

        if ($s === '') {
            return null;
        }

        if (ctype_digit($s)) {
            return self::ROMAN[(int) $s] ?? null;
        }

        return in_array($s, self::ROMAN, true) ? $s : null;
    }
}
