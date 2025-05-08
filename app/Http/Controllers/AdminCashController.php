<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AdminCash;
use App\Models\AdminCashes;

class AdminCashController extends Controller
{
    /**
     * GET /api/cashbox
     * List the current admin’s cash accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $rows = AdminCashes::where('admin_id', $request->user()->id)
                         ->with('organization:id,name')
                         ->latest()
                         ->get();

        return response()->json($rows);
    }

    /**
     * POST /api/cashbox
     * Create a new cash account – all logic lives here.
     */
    public function store(Request $request): JsonResponse
    {
        /** -----------------------------------------------------------------
         *  1)  Authorization
         *  ----------------------------------------------------------------*/
        if (! $request->user()->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(
                ['message' => 'You are not allowed to create cash accounts'],
                403
            );
        }

        /** -----------------------------------------------------------------
         *  2)  Validation  (name only)
         *  ----------------------------------------------------------------*/
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** -----------------------------------------------------------------
         *  3)  IBAN comes from the user’s organization account
         *  ----------------------------------------------------------------*/
        $org = $request->user()->organization;
        if (! $org) {
            return response()->json(
                ['message' => 'Admin is not linked to an organization'],
                422
            );
        }

        /** -----------------------------------------------------------------
         *  4)  Create the cash row
         *  ----------------------------------------------------------------*/
        $cash = AdminCashes::create([
            'organization_id' => $org->id,
            'admin_id'        => $request->user()->id,
            'name'            => $data['name'],
            'IBAN'            => $org->account,     // ← auto-filled
        ]);

        return response()->json($cash, 201);
    }
}
