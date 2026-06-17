<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SuperadminLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Pakai DB::table untuk User karena tidak punya BelongsToSchool trait
        // Pakai withoutTenant() untuk model yang punya SchoolScope
        $stats = [
            'total_schools'  => School::count(),
            'active_schools' => School::where('is_active', true)->count(),
            'total_users'    => DB::table('users')->count(),
            'total_students' => DB::table('users')->where('role', 'siswa')->count(),
            'trial_schools'  => School::where('subscription_plan', 'trial')->count(),
            'basic_schools'  => School::where('subscription_plan', 'basic')->count(),
            'pro_schools'    => School::where('subscription_plan', 'pro')->count(),
            'expired_soon'   => School::where('subscription_until', '<=', now()->addDays(7))
                                      ->where('subscription_until', '>=', now())
                                      ->count(),
        ];

        $recent_schools = School::latest()->take(5)->get();
        $recent_logs    = SuperadminLog::withoutTenant()->latest()->take(10)->get();

        return view('superadmin.dashboard.index', compact('stats', 'recent_schools', 'recent_logs'));
    }

    public function schools()
    {
        $schools = School::withCount('users')->latest()->paginate(20);
        return view('superadmin.schools.index', compact('schools'));
    }

    public function users()
    {
        $users = DB::table('users')
            ->leftJoin('schools', 'users.school_id', '=', 'schools.id')
            ->select('users.*', 'schools.name as school_name')
            ->orderByDesc('users.created_at')
            ->paginate(20);
        return view('superadmin.users.index', compact('users'));
    }

    public function subscriptions()
    {
        $subscriptions = DB::table('subscriptions')
            ->leftJoin('schools', 'subscriptions.school_id', '=', 'schools.id')
            ->select('subscriptions.*', 'schools.name as school_name')
            ->orderByDesc('subscriptions.created_at')
            ->paginate(20);
        return view('superadmin.subscriptions.index', compact('subscriptions'));
    }

    public function logs()
    {
        $logs = SuperadminLog::withoutTenant()->latest()->paginate(30);
        return view('superadmin.logs.index', compact('logs'));
    }
}
