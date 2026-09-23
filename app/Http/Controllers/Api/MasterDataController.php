<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Role;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;

class MasterDataController extends Controller
{
    public function getPersonnelOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'ranks' => Rank::where('status', 'ACTIVE')->orderBy('order')->get(),
            'units' => Unit::where('status', 'ACTIVE')->with('children')->whereNull('parent_id')->get(),
            'all_units' => Unit::where('status', 'ACTIVE')->orderBy('name')->get(),
            'positions' => Position::where('status', 'ACTIVE')->orderBy('name')->get(),
            'roles' => Role::all(),
        ]);
    }
}
