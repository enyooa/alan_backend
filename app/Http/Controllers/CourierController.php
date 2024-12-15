<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourierController extends Controller
{
    public function getCourierDocuments(Request $request)
{
    $userId = $request->user()->id;

    $documents = DB::table('packer_documents')
        ->where('id_courier', $userId)
        ->get();

    return response()->json(['documents' => $documents], 200);
}

public function getCourierUsers()
{
    try {
        $clientUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'courier');
        })->get(['id', 'first_name', 'last_name', 'whatsapp_number']);

        return response()->json($clientUsers, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch client users', 'message' => $e->getMessage()], 500);
    }
}
}
