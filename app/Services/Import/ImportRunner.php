<?php

namespace App\Services\Import;

use App\Exceptions\SensitiveDataEncryptionException;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Menjalankan siklus impor satu ImportBatch:
 *  1. validate(): dry-run. Membaca seluruh file dan memeriksa semua baris TANPA menulis ke tabel data.
 *  2. processChunk(): dipanggil berulang oleh job antrean. Tiap panggilan dibatasi waktu (TIME_BUDGET) supaya aman
 *     dari batas waktu worker; posisi disimpan di processed_rows. File dibaca ulang dan tiap baris divalidasi ULANG
 *     sebelum disimpan, jadi hasilnya mengikuti kondisi database saat itu.
 *
 * Detail desain: PERENCANAAN_IMPOR_EXCEL.md bagian 3 (D3 sampai D9).
 */
class ImportRunner
{
    public const ERROR_LIMIT = 500;

    public const FAILURE_LIMIT = 200;

    /** Detik maksimum per potongan sebelum melanjutkan di job berikutnya. */
    public const TIME_BUDGET = 35;

    /** Berhenti bila layanan enkripsi gagal berturut-turut sebanyak ini (jangan membombardir layanan yang mati). */
    public const ENCRYPTION_STOP_AFTER = 3;

    public const DISK = 'local';

    public function validate(ImportBatch $batch): void
    {
        $importer = ImportRegistry::make($batch->type);

        try {
            $parsed = ImportSpreadsheet::read(Storage::disk(self::DISK)->path($batch->file_path), $importer);
        } catch (RuntimeException $e) {
            $this->fail($batch, $e->getMessage());

            return;
        }

        if ($parsed['missing'] !== []) {
            $this->fail($batch, 'Kolom wajib tidak ditemukan di baris judul: ' . implode(', ', $parsed['missing']) . '. Gunakan template terbaru dari menu Impor Data.');

            return;
        }

        if ($parsed['rows'] === []) {
            $this->fail($batch, 'Tidak ada baris data. Isi data mulai baris 2 pada lembar "Data".');

            return;
        }

        $importer->prepare($batch->school_id);

        $state = [];
        $errors = [];
        $truncated = false;
        $errorRows = 0;
        $willCreate = 0;
        $willSkip = 0;

        foreach ($parsed['rows'] as $line => $row) {
            $state['__line'] = $line;
            [$data, $rowErrors] = $importer->validateRow($row, $state);

            if ($data === null) {
                $errorRows++;

                foreach ($rowErrors as $error) {
                    if (count($errors) >= self::ERROR_LIMIT) {
                        $truncated = true;
                        break;
                    }

                    $errors[] = ['line' => $line, 'column' => $error['column'], 'message' => $error['message']];
                }

                continue;
            }

            empty($data['skip']) ? $willCreate++ : $willSkip++;
        }

        $notes = $importer->summaryNotes($state);

        if ($willSkip > 0) {
            $notes[] = "{$willSkip} baris sudah ada di sistem dan akan dilewati.";
        }
        if ($parsed['unknown'] !== []) {
            $notes[] = 'Kolom yang tidak dikenal diabaikan: ' . implode(', ', $parsed['unknown']) . '.';
        }
        if ($truncated) {
            $notes[] = 'Daftar galat dipotong (' . self::ERROR_LIMIT . ' pertama). Perbaiki dulu, lalu periksa ulang untuk melihat sisanya.';
        }

        $batch->update([
            'status' => ImportBatch::STATUS_VALIDATED,
            'total_rows' => count($parsed['rows']),
            'valid_rows' => $willCreate,
            'error_rows' => $errorRows,
            'processed_rows' => 0,
            'created_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
            'errors' => $errors,
            'failures' => null,
            'notes' => $notes,
            'message' => null,
        ]);
    }

    /**
     * Proses satu potongan.
     *
     * @return bool true bila batch selesai (sukses atau dihentikan), false bila masih ada baris dan job harus dikirim lagi
     */
    public function processChunk(ImportBatch $batch): bool
    {
        $importer = ImportRegistry::make($batch->type);

        try {
            $parsed = ImportSpreadsheet::read(Storage::disk(self::DISK)->path($batch->file_path), $importer);
        } catch (RuntimeException $e) {
            $this->fail($batch, 'File unggahan tidak dapat dibaca lagi: ' . $e->getMessage());

            return true;
        }

        $importer->prepare($batch->school_id);

        $rows = $parsed['rows'];
        $lines = array_keys($rows);
        $total = count($lines);
        $position = (int) $batch->processed_rows;
        $started = microtime(true);

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $failures = $batch->failures ?? [];
        $encryptionStreak = 0;
        $stopped = false;
        $state = []; // tanpa deteksi duplikat dalam file: baris yang sudah dibuat terlihat lewat database

        $credentialsHandle = null;
        if ($importer->createsAccounts()) {
            $dir = "imports/{$batch->school_id}/{$batch->id}";
            Storage::disk(self::DISK)->makeDirectory($dir);
            $credentialsHandle = fopen(Storage::disk(self::DISK)->path("{$dir}/akun.csv"), 'a');
        }

        $flush = function () use ($batch, &$created, &$skipped, &$failed, &$failures, &$position) {
            $batch->update([
                'processed_rows' => $position,
                'created_count' => $batch->created_count + $created,
                'skipped_count' => $batch->skipped_count + $skipped,
                'failed_count' => $batch->failed_count + $failed,
                'failures' => $failures === [] ? null : $failures,
            ]);
            $created = $skipped = $failed = 0;
        };

        for (; $position < $total; $position++) {
            if (microtime(true) - $started > self::TIME_BUDGET) {
                break;
            }

            $line = $lines[$position];
            $state['__line'] = $line;
            [$data] = $importer->validateRow($rows[$line], $state);

            if ($data === null) {
                continue; // sudah dilaporkan saat validasi
            }

            try {
                $result = $importer->importRow($data, $batch->school_id);
                $encryptionStreak = 0;

                if ($result['status'] === 'created') {
                    $created++;

                    if ($credentialsHandle && ! empty($result['credential'])) {
                        $c = $result['credential'];
                        fputcsv($credentialsHandle, [$c['peran'] ?? '', $c['nama'] ?? '', $c['kelas'] ?? '', $c['username'] ?? '', $c['password'] ?? '']);
                    }
                } else {
                    $skipped++;
                }
            } catch (SensitiveDataEncryptionException $e) {
                $failed++;
                $encryptionStreak++;
                $this->addFailure($failures, $line, 'layanan enkripsi data sensitif tidak tersedia');

                if ($encryptionStreak >= self::ENCRYPTION_STOP_AFTER) {
                    $position++;
                    $stopped = true;
                    break;
                }
            } catch (Throwable $e) {
                report($e);
                $failed++;
                $this->addFailure($failures, $line, $e instanceof RuntimeException ? $e->getMessage() : 'gagal menyimpan karena kesalahan sistem');
            }

            if (($position + 1) % 10 === 0) {
                $flush();
            }
        }

        if ($credentialsHandle) {
            fclose($credentialsHandle);
        }

        $flush();

        if ($stopped) {
            $this->finalize(
                $batch->refresh(),
                ImportBatch::STATUS_STOPPED,
                'Impor dihentikan karena layanan enkripsi data sensitif tidak tersedia. Baris yang sudah masuk aman. Setelah layanan pulih, unggah ulang file yang sama: baris yang sudah ada otomatis dilewati.'
            );

            return true;
        }

        if ($position >= $total) {
            $this->finalize($batch->refresh(), ImportBatch::STATUS_COMPLETED, null);

            return true;
        }

        return false;
    }

    /** Tandai batch gagal total (file tidak valid, dsb.) dan hapus file unggahan. */
    public function fail(ImportBatch $batch, string $message): void
    {
        $this->deleteUpload($batch);

        $batch->update([
            'status' => ImportBatch::STATUS_FAILED,
            'message' => $message,
            'finished_at' => now(),
        ]);
    }

    private function finalize(ImportBatch $batch, string $status, ?string $message): void
    {
        $disk = Storage::disk(self::DISK);
        $dir = "imports/{$batch->school_id}/{$batch->id}";
        $credentialsPath = null;

        if ($disk->exists("{$dir}/akun.csv")) {
            $credentialsPath = "{$dir}/akun.xlsx";
            ImportSpreadsheet::credentialsReport($disk->path("{$dir}/akun.csv"), $disk->path($credentialsPath));
            $disk->delete("{$dir}/akun.csv");

            // Tidak ada akun yang berhasil dibuat -> tidak ada laporan.
            if ($batch->created_count === 0) {
                $disk->delete($credentialsPath);
                $credentialsPath = null;
            }
        }

        $this->deleteUpload($batch);

        $batch->update([
            'status' => $status,
            'message' => $message,
            'credentials_path' => $credentialsPath,
            'finished_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
    }

    private function deleteUpload(ImportBatch $batch): void
    {
        $disk = Storage::disk(self::DISK);

        if ($batch->file_path && $disk->exists($batch->file_path)) {
            $disk->delete($batch->file_path);
        }
    }

    /** @param list<array{line: int, message: string}> $failures */
    private function addFailure(array &$failures, int $line, string $message): void
    {
        if (count($failures) < self::FAILURE_LIMIT) {
            $failures[] = ['line' => $line, 'message' => $message];
        }
    }
}
