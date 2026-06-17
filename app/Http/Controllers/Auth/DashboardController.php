<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Redirect user ke dashboard sesuai role-nya.
     */
    public function index()
    {
        $role = Auth::user()->role;

        $destinations = [
            'superadmin'  => route('superadmin.dashboard'),
            'kepsek'      => route('kepsek.dashboard'),
            'kurikulum'   => route('kurikulum.dashboard'),
            'tu'          => route('tu.dashboard'),
            'guru_mapel'  => route('guru.dashboard'),
            'wali_kelas'  => route('guru.dashboard'),
            'kesiswaan'   => route('kesiswaan.dashboard'),
            'bk'          => route('bk.dashboard'),
            'toolman'     => route('toolman.dashboard'),
            'siswa'       => route('siswa.dashboard'),
        ];

        $destination = $destinations[$role] ?? '/dashboard';

        return redirect($destination);
    }
}
