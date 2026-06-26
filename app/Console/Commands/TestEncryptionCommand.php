<?php

namespace App\Console\Commands;

use App\Services\EncryptionGrpcService;
use Illuminate\Console\Command;

class TestEncryptionCommand extends Command
{
    protected $signature = 'test:encryption {text=data rahasia siswa}';

    protected $description = 'Test koneksi ke EncryptionGrpcService (encrypt + decrypt roundtrip)';

    public function handle()
    {
        $text = $this->argument('text');

        $this->info("Plain text asli : {$text}");

        try {
            $service = new EncryptionGrpcService();

            $this->info('Mengirim request Encrypt...');
            $start = microtime(true);
            $encrypted = $service->encrypt($text);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $this->info("Encrypt sukses ({$duration} ms)");
            $this->line("Hasil encrypted : {$encrypted}");

            $this->info('Mengirim request Decrypt...');
            $start = microtime(true);
            $decrypted = $service->decrypt($encrypted);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $this->info("Decrypt sukses ({$duration} ms)");
            $this->line("Hasil decrypted : {$decrypted}");

            if ($decrypted === $text) {
                $this->info('✅ ROUNDTRIP BERHASIL - data cocok!');
            } else {
                $this->error('❌ MISMATCH - data tidak cocok setelah roundtrip!');
            }
        } catch (\Throwable $e) {
            $this->error('Gagal: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}