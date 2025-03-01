<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    // List all addresses for a user
    public function index($userId)
{
    $user = User::with('addresses')->findOrFail($userId);

    return response()->json([
        'message' => 'Addresses retrieved successfully',
        'addresses' => $user->addresses,
    ]);
}

public function storeAdress(Request $request, $userId)
{
    Log::info('Store method called with userId: '.$userId);
    Log::info('Request data: '.json_encode($request->all()));
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $user = User::findOrFail($userId);

    // Create the address
    $address = Address::create(['name' => $request->name]);

    // Attach the address to the user
    $user->addresses()->attach($address->id);

    return response()->json([
        'message' => 'Address created successfully',
        'address' => $address,
    ], 201);
}

public function getClientAddresses()
{
    // Get users with the "client" role
    $clients = User::whereHas('roles', function ($query) {
        $query->where('name', 'client'); // Filter users with the "client" role
    })->with('addresses') // Eager load addresses relationship
      ->get();

    // Prepare the response
    $result = $clients->map(function ($client) {
        return [
            'client_id' => $client->id,
            'client_name' => $client->first_name . ' ' . $client->last_name,
            'addresses' => $client->addresses->map(function ($address) {
                return [
                    'id' => $address->id, // Include address ID
                    'name' => $address->name,
                ];
            }),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $result,
    ]);
}

public function getStorageUserAddresses()
{
    // Get users with the "storage" role
    $storageUsers = User::whereHas('roles', function ($query) {
        $query->where('name', 'storager'); // Filter users with the "storage" role
    })->with('addresses') // Eager load addresses relationship
      ->get();

    // Prepare the response
    $result = $storageUsers->map(function ($user) {
        return [
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'addresses' => $user->addresses->map(function ($address) {
                return [
                    'id' => $address->id, // Include address ID
                    'name' => $address->name, // Include address name
                ];
            }),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $result,
    ]);
}



public function update(Request $request, $addressId)
{
    $request->validate([
        'name' => 'required|string|max:255', // Add max length
    ]);

    $address = Address::findOrFail($addressId);

    $address->update($request->all());

    return response()->json(['message' => 'Address updated successfully', 'address' => $address]);
}

    // Delete an address
    public function destroy($addressId)
{
    $address = Address::findOrFail($addressId);

    $address->delete();

    return response()->json(['message' => 'Адреса успешно удалены!'], 200);
}

}
