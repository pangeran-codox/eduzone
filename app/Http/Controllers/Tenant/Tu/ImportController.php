<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tu\StoreImportRequest;
use App\Jobs\ProcessImportChunk;
use App\Models\ImportBatch;
use App\Services\Import\ImportRegistry;
use App\Services\Import\ImportRunner;
use App\Services\Import\ImportSpreadsheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Impor Data (TU): Kelas, Siswa, Guru lewat template Excel. Lihat PERENCANAAN_IMPOR_EXCEL.md.
 * Alur: unduh template -> unggah (validasi dry-run, tidak menyimpan apa pun) -> pratinjau -> konfirmasi -> impor di antrean.
 */
class ImportController extends Controller
{
    /** Langkah berikutnya yang disarankan setelah satu jenis selesai diimpor. */
    private const NEXT = ['kelas' => 'siswa', 'siswa' => 'guru'];

    public function index(): View
    {
        $schoolId = auth()->user()->school_id;

        $importers = [];
        foreach (ImportRegistry::all() as $type => $class) {
            $importers[$type] = new $class();
        }

        $batches = ImportBatch::query()
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(10)
            ->get();

        return view('tenant.tu.impor.index', [
            'importers' => $importers,
            'batches' => $batches,
        ]);
    }

    public function template(string $type): BinaryFileResponse
    {
        abort_unless(ImportRegistry::has($type), 404);

        $path = ImportSpreadsheet::template(ImportRegistry::make($type), auth()->user()->school_id);

        return response()->download($path, "template-impor-{$type}.xlsx")->deleteFileAfterSend(true);
    }

    public function store(StoreImportRequest $request, ImportRunner $runner): RedirectResponse
    {
        $user = $request->user();
        $file = $request->file('file');

        $batch = ImportBatch::create([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'type' => $request->validated('type'),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $file->store("imports/{$user->school_id}", ImportRunner::DISK),
            'status' => ImportBatch::STATUS_VALIDATED,
        ]);

        try {
            $runner->validate($batch);
        } catch (Throwable $e) {
            report($e);
            $runner->fail($batch, 'File tidak dapat diperiksa karena kesalahan sistem. Coba lagi, atau hubungi admin bila berulang.');
        }

        return redirect()->route('tu.impor.show', $batch);
    }

    public function show(ImportBatch $batch): View
    {
        $this->authorizeBatch($batch);

        return view('tenant.tu.impor.show', [
            'batch' => $batch,
            'importer' => ImportRegistry::make($batch->type),
            'next' => self::NEXT[$batch->type] ?? null,
            'credentialsAvailable' => $this->credentialsAvailable($batch),
        ]);
    }

    public function progress(ImportBatch $batch): JsonResponse
    {
        $this->authorizeBatch($batch);

        return response()->json([
            'status' => $batch->status,
            'finished' => $batch->isFinished(),
            'percent' => $batch->progressPercent(),
            'processed' => $batch->processed_rows,
            'total' => $batch->total_rows,
            'created' => $batch->created_count,
            'skipped' => $batch->skipped_count,
            'failed' => $batch->failed_count,
        ]);
    }

    public function confirm(ImportBatch $batch): RedirectResponse
    {
        $this->authorizeBatch($batch);

        if ($batch->status !== ImportBatch::STATUS_VALIDATED || $batch->valid_rows < 1) {
            return redirect()->route('tu.impor.show', $batch)->with('error', 'Tidak ada data baru yang dapat diimpor dari unggahan ini.');
        }

        // Atomik: dua klik "Impor" bersamaan hanya menjalankan satu job.
        $claimed = ImportBatch::query()
            ->whereKey($batch->id)
            ->where('status', ImportBatch::STATUS_VALIDATED)
            ->update([
                'status' => ImportBatch::STATUS_QUEUED,
                'confirmed_at' => now(),
                'processed_rows' => 0,
                'created_count' => 0,
                'skipped_count' => 0,
                'failed_count' => 0,
                'failures' => null,
            ]);

        if ($claimed) {
            ProcessImportChunk::dispatch($batch->id);
        }

        return redirect()->route('tu.impor.show', $batch);
    }

    public function errors(ImportBatch $batch): BinaryFileResponse
    {
        $this->authorizeBatch($batch);

        $truncated = collect($batch->notes ?? [])->contains(fn ($note) => str_contains((string) $note, 'dipotong'));
        $path = ImportSpreadsheet::errorReport($batch->errors ?? [], $truncated);

        return response()->download($path, "galat-impor-{$batch->type}.xlsx")->deleteFileAfterSend(true);
    }

    public function credentials(ImportBatch $batch): StreamedResponse
    {
        $this->authorizeBatch($batch);
        abort_unless($this->credentialsAvailable($batch), 404);

        // Tetap tersedia sampai kedaluwarsa (24 jam) supaya unduhan yang terputus bisa diulang; dihapus oleh `imports:purge`.
        return Storage::disk(ImportRunner::DISK)->download(
            $batch->credentials_path,
            "akun-{$batch->type}-" . now()->format('Ymd-His') . '.xlsx'
        );
    }

    private function authorizeBatch(ImportBatch $batch): void
    {
        abort_if($batch->school_id !== auth()->user()->school_id, 404);
    }

    private function credentialsAvailable(ImportBatch $batch): bool
    {
        return $batch->credentials_path
            && $batch->expires_at
            && $batch->expires_at->isFuture()
            && Storage::disk(ImportRunner::DISK)->exists($batch->credentials_path);
    }
}
