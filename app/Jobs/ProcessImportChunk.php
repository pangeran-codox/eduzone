<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Import\ImportRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Memproses satu potongan impor (dibatasi waktu di ImportRunner::TIME_BUDGET), lalu mengirim dirinya lagi bila masih ada baris.
 * Sengaja SADAR TENANT (bawaan paket multitenancy): job dikirim dari konteks TU yang punya tenant, jadi `tenantId` ikut di payload.
 * Ini kebalikan dari notifikasi pendaftaran (NotTenantAware) yang dikirim dari konteks tanpa tenant.
 * Hanya memegang id batch; model dimuat ulang tanpa global scope agar tidak bergantung pada kondisi tenant saat job berjalan.
 */
class ProcessImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(public string $batchId)
    {
    }

    public function handle(ImportRunner $runner): void
    {
        $batch = ImportBatch::withoutGlobalScopes()->find($this->batchId);

        if (! $batch || $batch->isFinished()) {
            return;
        }

        if ($batch->status !== ImportBatch::STATUS_RUNNING) {
            $batch->update(['status' => ImportBatch::STATUS_RUNNING]);
        }

        if (! $runner->processChunk($batch)) {
            self::dispatch($this->batchId);
        }
    }

    public function failed(Throwable $e): void
    {
        $batch = ImportBatch::withoutGlobalScopes()->find($this->batchId);

        if ($batch && ! $batch->isFinished()) {
            app(ImportRunner::class)->fail(
                $batch,
                'Impor berhenti karena kesalahan sistem (' . class_basename($e) . '). Baris yang sudah masuk tetap aman; unggah ulang file yang sama untuk melanjutkan, baris yang sudah ada otomatis dilewati.'
            );
        }
    }
}
