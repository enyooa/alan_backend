<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index()
    {
        return response()->json(Organization::all(), 200);
    }

    public function store(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string',
        'current_accounts' => 'required|string',
    ]);

    $organization = Organization::create($validatedData);

    if ($organization) {
        return response()->json(['success' => true, 'organization' => $organization], 201);
    } else {
        return response()->json(['success' => false, 'message' => 'Failed to create organization'], 500);
    }
}


    public function update(Request $request, Organization $organization)
    {
        $organization->update($request->all());
        return response()->json($organization, 200);
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();
        return response()->json(['message' => 'Organization deleted'], 200);
    }
}
