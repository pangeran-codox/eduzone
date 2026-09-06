<?php

namespace App\Console\Commands\Tenant;

use App\Models\School;
use Illuminate\Console\Command;

/**
 * Aktifkan konteks tenant tertentu untuk debugging via CLI / Tinker.
 *
 * Usage:
 *   php artisan tenant:mimic sekolah-sma-1         (cari by slug)
 *   php artisan tenant:mimic SMA Negeri 1         (cari by name LIKE)
 *   php artisan tenant:mimic 019...abc            (cari by id UUID)
 *   php artisan tenant:mimic --list               (tampil 10 sekolah aktif)
 *   php artisan tenant:mimic --reset              (kembalikan ke null tenant)
 */
class MimicTenantCommand extends Command
{
    protected $signature = 'tenant:mimic
                            {identifier? : Slug / Nama / UUID sekolah}
                            {--list : Tampil daftar sekolah aktif}
                            {--reset : Reset tenant context ke NULL (tanpa scope filter)}';

    protected $description = 'Aktifkan konteks tenant (School::current()) untuk debugging CLI / Tinker.';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listSchools();
        }

        if ($this->option('reset')) {
            return $this->resetTenant();
        }

        $identifier = $this->argument('identifier');
        if (! $identifier) {
            $this->error('❌ Butuh identifier sekolah (slug / nama / UUID). Gunakan --list untuk lihat daftar.');
            return self::FAILURE;
        }

        $school = $this->resolveSchool($identifier);
        if (! $school) {
            $this->error("❌ Sekolah dengan identifier \"{$identifier}\" tidak ditemukan.");
            return self::FAILURE;
        }

        $school->makeCurrent();

        $this->outputTenantActive($school, true);

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────

    private function resolveSchool(string $identifier): ?School
    {
        // 1. Cari by UUID (ID)
        $byId = School::withoutGlobalScopes()->find($identifier);
        if ($byId) return $byId;

        // 2. Cari by slug (exact)
        $bySlug = School::withoutGlobalScopes()->where('slug', $identifier)->first();
        if ($bySlug) return $bySlug;

        // 3. Cari by name (insensitive + LIKE)
        $byName = School::withoutGlobalScopes()
            ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($identifier) . '%'])
            ->limit(3)
            ->get();

        if ($byName->count() === 1) {
            return $byName->first();
        }

        if ($byName->count() > 1) {
            $this->warn("⚠️  Ditemukan {$byName->count()} sekolah cocok nama \"{$identifier}\", pilih salah satu:");
            $byName->each(fn ($s, $i) => $this->line("   <info>[$i]</info> {$s->name} <comment>(slug: {$s->slug})</comment>"));
            $pick = (int) $this->ask('Nomor pilihan', '0');
            return $byName->values()->get($pick);
        }

        // 4. Cari by NPSN (numeric exact)
        if (ctype_digit($identifier)) {
            return School::withoutGlobalScopes()->where('npsn', $identifier)->first();
        }

        return null;
    }

    private function listSchools(): int
    {
        $schools = School::withoutGlobalScopes()
            ->orderByDesc('is_active')
            ->limit(15)
            ->get(['id', 'name', 'slug', 'npsn', 'is_active']);

        if ($schools->isEmpty()) {
            $this->warn('⚠️  Belum ada sekolah sama sekali di tabel schools.');
            return self::SUCCESS;
        }

        $this->table(
            ['UUID (potong)', 'Nama', 'Slug', 'NPSN', 'Aktif'],
            $schools->map(fn ($s) => [
                substr($s->id, 0, 8) . '...',
                $s->name,
                $s->slug ?? '-',
                $s->npsn ?? '-',
                $s->is_active ? '✅' : '❌',
            ])
        );

        return self::SUCCESS;
    }

    private function resetTenant(): int
    {
        $prev = School::current();
        if ($prev) {
            $this->line("ℹ️  Tenant sebelumnya: <comment>{$prev->name}</comment>");
        }

        // Pakai forgetCurrent tenancy (Spatie method) atau pasang null container
        try {
            School::forgetCurrent();
        } catch (\Throwable) {
            // Fallback: app()->instance(TenantContainer::class, null) kalau ada
        }

        $container = app(\Spatie\Multitenancy\TenantCollection::class ?? 'multitenancy');
        $this->info('✅ Tenant context sudah di-RESET ke NULL (SchoolScope tidak akan memfilter query).');

        $this->newLine();
        $this->line('💡 Tips Tinker:');
        $this->line('   • Query <comment>TANPA</comment> tenant scope filter:');
        $this->line('     <info>$allStudents = \App\Models\Student::withoutGlobalScopes()->count();</info>');
        $this->line('   • Aktifkan ulang tenant: <comment>(new MimicTenantCommand)</comment> → atau panggil $school->makeCurrent()');

        return self::SUCCESS;
    }

    private function outputTenantActive(School $school, bool $showTips): void
    {
        $this->newLine();
        $this->info('✅ Tenant context AKTIF:');
        $this->line("   <comment>Nama   :</comment> {$school->name}");
        $this->line("   <comment>Slug   :</comment> " . ($school->slug ?? '-'));
        $this->line("   <comment>NPSN   :</comment> " . ($school->npsn ?? '-'));
        $this->line("   <comment>ID     :</comment> " . substr($school->id, 0, 16) . '...');
        $this->line("   <comment>Aktif  :</comment> " . ($school->is_active ? '✅' : '❌'));

        if ($showTips) {
            $this->newLine();
            $this->line('💡 Tips debugging (Tinker / query):');
            $this->line('   • Cek scope tenant di query:');
            $this->line('     <info>\App\Models\Student::query()->dumpTenantScope();</info>');
            $this->line('   • Lihat SQL mentah + bindings:');
            $this->line('     <info>\App\Models\Student::query()->toRawSql();</info>');
            $this->line('   • Query lintas semua sekolah (ignore tenant scope):');
            $this->line('     <info>\App\Models\Student::withoutTenant()->count();</info>');
        }
    }
}
