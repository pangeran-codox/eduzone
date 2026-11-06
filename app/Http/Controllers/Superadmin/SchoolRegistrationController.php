<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\RejectSchoolRegistrationRequest;
use App\Models\School;
use App\Models\SchoolRegistration;
use App\Models\User;
use App\Services\SchoolRegistrationService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Antrean pendaftaran sekolah untuk superadmin. Logika bisnis ada di SchoolRegistrationService.
 * Asumsi: superadmin adalah User (school_id null) pada guard default, jadi $request->user() valid
 * dan cocok dengan FK reviewed_by -> users. Konfirmasi saat uji.
 */
class SchoolRegistrationController extends Controller
{
    public const STATUSES = [
        SchoolRegistration::STATUS_PENDING_APPROVAL => 'Menunggu persetujuan',
        SchoolRegistration::STATUS_PENDING_EMAIL => 'Menunggu verifikasi email',
        SchoolRegistration::STATUS_APPROVED => 'Disetujui',
        SchoolRegistration::STATUS_REJECTED => 'Ditolak',
        SchoolRegistration::STATUS_EXPIRED => 'Kedaluwarsa',
    ];

    public function index(Request $request): View
    {
        $status = $request->input('status', SchoolRegistration::STATUS_PENDING_APPROVAL);

        if ($status !== 'all' && ! array_key_exists($status, self::STATUSES)) {
            $status = SchoolRegistration::STATUS_PENDING_APPROVAL;
        }

        $registrations = SchoolRegistration::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('school_name', 'ilike', $term)
                        ->orWhere('npsn', 'ilike', $term)
                        ->orWhere('applicant_email', 'ilike', $term);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.registrations.index', [
            'registrations' => $registrations,
            'statuses' => self::STATUSES,
            'statusFilter' => $status,
            'search' => $request->input('search', ''),
            'pendingCount' => SchoolRegistration::query()
                ->where('status', SchoolRegistration::STATUS_PENDING_APPROVAL)
                ->count(),
        ]);
    }

    public function show(SchoolRegistration $registration): View
    {
        $registration->load(['school', 'reviewer', 'user']);

        // Peringatan untuk superadmin sebelum approve. Pendaftaran yang sudah disetujui dikecualikan
        // dari pengecekan, karena sekolah/akun miliknya sendiri pasti cocok.
        $npsnTaken = (bool) ($registration->npsn
            && School::query()
                ->where('npsn', $registration->npsn)
                ->when($registration->school_id, fn ($q) => $q->where('id', '!=', $registration->school_id))
                ->exists());

        $emailTaken = User::query()
            ->where('email', $registration->applicant_email)
            ->when($registration->user_id, fn ($q) => $q->where('id', '!=', $registration->user_id))
            ->exists();

        return view('superadmin.registrations.show', [
            'registration' => $registration,
            'statuses' => self::STATUSES,
            'npsnTaken' => $npsnTaken,
            'emailTaken' => $emailTaken,
        ]);
    }

    public function approve(Request $request, SchoolRegistration $registration, SchoolRegistrationService $service): RedirectResponse
    {
        try {
            $service->approve($registration, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'Gagal menyetujui pendaftaran karena kesalahan database. Tidak ada data yang tersimpan; coba lagi atau cek log.');
        }

        return redirect()
            ->route('superadmin.registrations.show', $registration)
            ->with('success', "Sekolah \"{$registration->school_name}\" disetujui. Email pembuatan kata sandi dikirim ke {$registration->applicant_email}.");
    }

    public function reject(RejectSchoolRegistrationRequest $request, SchoolRegistration $registration, SchoolRegistrationService $service): RedirectResponse
    {
        try {
            $service->reject($registration, $request->user(), $request->validated('reason'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('superadmin.registrations.index')
            ->with('success', "Pendaftaran \"{$registration->school_name}\" ditolak dan pendaftar sudah diberi tahu lewat email.");
    }

    public function resendPasswordSetup(SchoolRegistration $registration, SchoolRegistrationService $service): RedirectResponse
    {
        try {
            $service->resendPasswordSetup($registration);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Tautan baru untuk membuat kata sandi dikirim ke {$registration->applicant_email}.");
    }
}
