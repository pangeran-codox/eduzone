<?php

namespace App\Multitenancy\TenantFinder;

use App\Models\School;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class AuthUserTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        // Superadmin tidak terikat ke school manapun
        if ($user->role === 'superadmin' || is_null($user->school_id)) {
            return null;
        }

        return School::find($user->school_id);
    }
}
