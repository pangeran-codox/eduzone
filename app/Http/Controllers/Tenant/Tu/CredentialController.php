<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Http\Controllers\Controller;
use App\Models\Absensi\Credential;
use App\Models\Absensi\PeopleRef;
use App\Models\SchoolClass;
use App\Services\Absensi\GatewayTokenIssuer;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Enrollment kredensial QR untuk siswa/guru/staff, dilakukan TU.
 *
 * ALUR: Laravel TIDAK menghitung/menyimpan hash sendiri - itu tanggung
 * jawab absensi-gateway (lihat api_contract.md Bagian 3). Laravel cuma
 * generate token acak (credential_value mentah), kirim ke gateway lewat
 * POST /enrollment/credentials (JWT admin dari GatewayTokenIssuer),
 * gateway yang hash & simpan ke `credentials`.
 *
 * Token mentah SENGAJA TIDAK PERNAH disimpan permanen di Laravel (bukan
 * DB, bukan cache) - cuma numpang lewat di session flash untuk 1x
 * redirect ke halaman cetak, lalu hilang begitu session flash itu
 * dibaca. Kalau kertas hasil cetak hilang, solusinya generate ulang
 * (kredensial lama otomatis "kalah" begitu yang baru dibuat untuk
 * person_id yang sama - gateway yang atur ini, bukan Laravel), BUKAN
 * menampilkan ulang token lama yang sudah tidak ada di sisi kami.
 */
class CredentialController extends Controller
{
    private const PERSON_TYPES = ['student', 'teacher', 'staff'];

    public function index(Request $request)
    {
        $schoolId = $request->user()->school_id;
        $classId = $request->query('class_id');
        $personType = $request->query('person_type', 'student');

        $classes = SchoolClass::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('grade')
            ->orderBy('class_group')
            ->get();

        $peopleQuery = PeopleRef::where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('person_type', $personType);

        if ($personType === 'student' && $classId) {
            $peopleQuery->where('class_id', $classId);
        }

        $people = $personType === 'student' && ! $classId
            ? collect()
            : $peopleQuery->orderBy('full_name')->get();

        $activeCredentials = Credential::where('school_id', $schoolId)
            ->where('method', 'qr')
            ->where('is_active', true)
            ->whereIn('person_id', $people->pluck('person_id'))
            ->get()
            ->keyBy(fn ($c) => $c->person_type . ':' . $c->person_id);

        $rows = $people->map(fn ($p) => [
            'person_id' => $p->person_id,
            'person_type' => $p->person_type,
            'full_name' => $p->full_name,
            'has_credential' => $activeCredentials->has($p->person_type . ':' . $p->person_id),
        ]);

        return view('tenant.tu.kredensial.index', [
            'classes' => $classes,
            'selectedClassId' => $classId,
            'personType' => $personType,
            'rows' => $rows,
        ]);
    }

    public function generate(Request $request, string $personType, string $personId)
    {
        if (! in_array($personType, self::PERSON_TYPES, true)) {
            abort(404);
        }

        $schoolId = $request->user()->school_id;

        $person = PeopleRef::where('school_id', $schoolId)
            ->where('person_id', $personId)
            ->where('person_type', $personType)
            ->where('is_active', true)
            ->first();

        if (! $person) {
            return back()->withErrors(['person' => 'Orang tidak ditemukan atau tidak aktif.']);
        }

        try {
            $token = $this->enrollQr($request->user(), $schoolId, $personId, $personType);
        } catch (\Throwable $e) {
            Log::error('Gagal enroll kredensial QR', ['error' => $e->getMessage(), 'person_id' => $personId]);

            return back()->withErrors(['gateway' => $e->getMessage()]);
        }

        return redirect()->route('tu.kredensial.print')->with('printBatch', [
            ['nama' => $person->full_name, 'token' => $token],
        ]);
    }

    public function generateForClass(Request $request, string $classId)
    {
        $schoolId = $request->user()->school_id;

        $students = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', 'student')
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        $printBatch = [];
        $failed = [];

        foreach ($students as $student) {
            try {
                $token = $this->enrollQr($request->user(), $schoolId, $student->person_id, 'student');
                $printBatch[] = ['nama' => $student->full_name, 'token' => $token];
            } catch (\Throwable $e) {
                Log::error('Gagal enroll kredensial QR (batch)', [
                    'error' => $e->getMessage(),
                    'person_id' => $student->person_id,
                ]);
                $failed[] = $student->full_name;
            }
        }

        if (empty($printBatch)) {
            return back()->withErrors(['gateway' => 'Semua percobaan generate gagal. Coba lagi nanti.']);
        }

        $redirect = redirect()->route('tu.kredensial.print')->with('printBatch', $printBatch);

        if ($failed) {
            $redirect->with('warning', 'Gagal untuk: ' . implode(', ', $failed));
        }

        return $redirect;
    }

    public function generateForType(Request $request, string $personType)
    {
        if (! in_array($personType, ['teacher', 'staff'], true)) {
            abort(404); // siswa tetap wajib lewat generateForClass, bukan sini
        }

        $schoolId = $request->user()->school_id;

        $people = PeopleRef::where('school_id', $schoolId)
            ->where('person_type', $personType)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        $printBatch = [];
        $failed = [];

        foreach ($people as $person) {
            try {
                $token = $this->enrollQr($request->user(), $schoolId, $person->person_id, $personType);
                $printBatch[] = ['nama' => $person->full_name, 'token' => $token];
            } catch (\Throwable $e) {
                Log::error('Gagal enroll kredensial QR (batch tipe)', [
                    'error' => $e->getMessage(),
                    'person_id' => $person->person_id,
                ]);
                $failed[] = $person->full_name;
            }
        }

        if (empty($printBatch)) {
            return back()->withErrors(['gateway' => 'Semua percobaan generate gagal. Coba lagi nanti.']);
        }

        $redirect = redirect()->route('tu.kredensial.print')->with('printBatch', $printBatch);

        if ($failed) {
            $redirect->with('warning', 'Gagal untuk: ' . implode(', ', $failed));
        }

        return $redirect;
    }

    public function print(Request $request)
    {
        $batch = $request->session()->get('printBatch');

        if (! $batch) {
            return redirect()->route('tu.kredensial.index')
                ->withErrors(['print' => 'Tidak ada QR untuk dicetak. Generate dulu dari halaman sebelumnya.']);
        }

        $writer = new SvgWriter();

        $rendered = collect($batch)->map(function ($item) use ($writer) {
            $qrCode = new QrCode(
                data: $item['token'],
                // ISO-8859-1 lebih kompatibel buat scanner fisik dibanding
                // UTF-8 default - aman di sini karena token cuma hex chars.
                encoding: new Encoding('ISO-8859-1'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 200,
                margin: 5,
            );

            $result = $writer->write($qrCode);

            return ['nama' => $item['nama'], 'qr_svg' => $result->getString()];
        });

        return view('tenant.tu.kredensial.print', ['batch' => $rendered]);
    }

    /**
     * @throws \RuntimeException kalau gateway menolak atau tidak bisa dihubungi
     */
    private function enrollQr($user, string $schoolId, string $personId, string $personType): string
    {
        $rawToken = bin2hex(random_bytes(24)); // credential_value mentah, isi QR

        $jwt = (new GatewayTokenIssuer())->issueForAdmin($user->id, $schoolId);
        $baseUrl = config('services.absensi_gateway.base_url');

        try {
            $response = Http::withToken($jwt)
                ->timeout(8)
                ->post("{$baseUrl}/api/v1/enrollment/credentials", [
                    'person_id' => $personId,
                    'person_type' => $personType,
                    'method' => 'qr',
                    'credential_value' => $rawToken,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Layanan absensi sedang tidak bisa dihubungi.');
        }

        if ($response->failed()) {
            throw new \RuntimeException($response->json('message') ?? 'Gateway menolak permintaan enrollment.');
        }

        return $rawToken;
    }
}