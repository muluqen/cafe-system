<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) return response()->json([]);

        return RolePermission::where('restaurant_id', $restaurantId)->get();
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        if (!$restaurantId) return response()->json(['message' => 'Unauthorized'], 403);

        $validated = $request->validate([
            'staff_role' => 'required|string',
            'entity_key' => 'required|string',
            'can_read' => 'required|boolean',
            'can_write' => 'required|boolean',
        ]);

        $permission = RolePermission::updateOrCreate(
            [
                'restaurant_id' => $restaurantId,
                'staff_role' => $validated['staff_role'],
                'entity_key' => $validated['entity_key']
            ],
            [
                'can_read' => $validated['can_read'],
                'can_write' => $validated['can_write'],
            ]
        );

        return response()->json($permission);
    }
}
