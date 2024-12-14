<?php

namespace App\Http\Controllers;

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

}
