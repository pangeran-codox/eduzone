<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\StoreSubscriptionRequest;
use App\Http\Requests\Superadmin\UpdateSubscriptionRequest;
use App\Models\School;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public const PLANS = ['trial' => 'Trial', 'basic' => 'Basic', 'pro' => 'Pro'];
    public const STATUSES = ['active' => 'Aktif', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'];

    public function index(Request $request): View
    {
        $subscriptions = Subscription::query()
            ->withoutTenant()
            ->when($request->filled('school'), fn ($q) => $q->where('school_id', $request->input('school')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('invoice_no', 'ilike', '%'.$request->input('search').'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $schoolNames = School::query()
            ->whereIn('id', $subscriptions->pluck('school_id'))
            ->pluck('name', 'id');

        foreach ($subscriptions as $sub) {
            $sub->school_name = $schoolNames[$sub->school_id] ?? '—';
        }

        return view('superadmin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'plans' => self::PLANS,
            'statuses' => self::STATUSES,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'search' => $request->input('search', ''),
            'schoolFilter' => $request->input('school', ''),
            'statusFilter' => $request->input('status', ''),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.subscriptions.create', [
            'plans' => self::PLANS,
            'statuses' => self::STATUSES,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $subscription = Subscription::create($data);

        // Kalau langganan yang baru dicatat ini statusnya aktif, sinkronkan
        // juga ke kolom cache di tabel schools (subscription_plan,
        // subscription_until) — supaya halaman Sekolah & stat dashboard
        // yang baca kolom itu langsung ikut ter-update, tanpa perlu join ke
        // sini tiap saat.
        if ($data['status'] === 'active') {
            School::where('id', $data['school_id'])->update([
                'subscription_plan' => $data['plan'],
                'subscription_until' => $data['expired_at'],
            ]);
        }

        return redirect()
            ->route('superadmin.subscriptions.index')
            ->with('success', 'Langganan berhasil dicatat.');
    }

    public function edit(Subscription $subscription): View
    {
        return view('superadmin.subscriptions.edit', [
            'subscription' => $subscription,
            'plans' => self::PLANS,
            'statuses' => self::STATUSES,
            'school' => School::find($subscription->school_id),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validated();

        $subscription->update($data);

        // Sinkron ulang ke schools kalau statusnya aktif — sama alasannya
        // dengan store(). Kalau diubah jadi cancelled/expired, kolom di
        // schools SENGAJA tidak otomatis dikosongkan (biar superadmin yang
        // putuskan manual paket pengganti apa, lewat catat langganan baru).
        if ($data['status'] === 'active') {
            School::where('id', $subscription->school_id)->update([
                'subscription_plan' => $data['plan'],
                'subscription_until' => $data['expired_at'],
            ]);
        }

        return redirect()
            ->route('superadmin.subscriptions.index')
            ->with('success', 'Langganan berhasil diperbarui.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()
            ->route('superadmin.subscriptions.index')
            ->with('success', 'Riwayat langganan berhasil dihapus.');
    }
}
