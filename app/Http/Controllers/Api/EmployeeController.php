<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $users = User::excludeDevs()->with(['role', 'organization', 'department', 'designation', 'manager'])
            ->where('organization_id', $request->user()->organization_id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show($id)
    {
        $user = User::with(['role', 'organization', 'department', 'designation', 'manager'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}
