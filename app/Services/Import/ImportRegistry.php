<?php

namespace App\Services\Import;

use InvalidArgumentException;

/**
 * Daftar jenis impor yang tersedia. Urutan = urutan yang disarankan ke TU (Kelas dulu, karena Siswa dan Jadwal merujuk ke kelas).
 * Menambah jenis baru (mis. 'jadwal', 'staff') = membuat satu kelas turunan BaseImporter dan mendaftarkannya di sini.
 */
class ImportRegistry
{
    /** @return array<string, class-string<BaseImporter>> */
    public static function all(): array
    {
        return [
            'kelas' => ClassImporter::class,
            'siswa' => StudentImporter::class,
            'guru' => TeacherImporter::class,
        ];
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::all());
    }

    public static function make(string $type): BaseImporter
    {
        $class = self::all()[$type] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Jenis impor tidak dikenal: {$type}");
        }

        return new $class();
    }
}
