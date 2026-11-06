<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Services\Import\ImportRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Laporan akun berisi kata sandi awal dalam teks biasa, dan file unggahan berisi data pribadi: keduanya tidak boleh menumpuk.
 * - laporan akun dihapus setelah kedaluwarsa (24 jam setelah impor selesai)
 * - unggahan yang tidak pernah dikonfirmasi (status validated) dihapus setelah 24 jam
 */
class PurgeImportFiles extends Command
{
    protected $signature = 'imports:purge';

    protected $description = 'Hapus laporan akun impor yang kedaluwarsa dan file unggahan yang tidak pernah dikonfirmasi';

    public function handle(): int
    {
        $disk = Storage::disk(ImportRunner::DISK);
        $reports = 0;
        $uploads = 0;

        ImportBatch::withoutGlobalScopes()
            ->whereNotNull('credentials_path')
            ->where('expires_at', '<', now())
            ->get()
            ->each(function (ImportBatch $batch) use ($disk, &$reports) {
                if ($disk->exists($batch->credentials_path)) {
                    $disk->delete($batch->credentials_path);
                }

                $batch->update(['credentials_path' => null]);
                $reports++;
            });

        ImportBatch::withoutGlobalScopes()
            ->where('status', ImportBatch::STATUS_VALIDATED)
            ->where('created_at', '<', now()->subDay())
            ->get()
            ->each(function (ImportBatch $batch) use ($disk, &$uploads) {
                if ($batch->file_path && $disk->exists($batch->file_path)) {
                    $disk->delete($batch->file_path);
                }

                $batch->update([
                    'status' => ImportBatch::STATUS_FAILED,
                    'message' => 'Kedaluwarsa: unggahan tidak dikonfirmasi dalam 24 jam, file dihapus. Unggah ulang bila masih diperlukan.',
                    'finished_at' => now(),
                ]);
                $uploads++;
            });

        $this->info("{$reports} laporan akun dan {$uploads} unggahan dihapus.");

        return self::SUCCESS;
    }
}
