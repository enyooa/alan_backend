<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Get all users (only accessible by admin)
    public function index()
{
    $users = User::with('roles')->get();
    return response()->json($users);
}
    // админ присвоет роль
    public function assignRoles(Request $request, User $user)
{
    $request->validate([
        'role' => 'string|exists:roles,name',
    ]);

    Log::info('User ID: ' . $user->id); // Logs the user being updated
    Log::info('Role: ' . $request->role); // Logs the role being assigned

    $role = Role::where('name', $request->role)->first();
    if ($role) {
        $user->roles()->attach($role);
    } else {
        return response()->json(['message' => 'Role not found'], 404);
    }

    return response()->json(['message' => 'Role assigned successfully']);
}


public function removeRole(Request $request, User $user)
{
    $request->validate([
        'role' => 'string|exists:roles,name',
    ]);

    $role = Role::where('name', $request->role)->first();
    if ($role) {
        $user->roles()->detach($role);
    }

    return response()->json(['message' => 'Role removed successfully']);
}
// удалить роль 

    // Store a new user (create employee)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'nullable',
            'surname' => 'nullable',
            'whatsapp_number' => 'required|unique:users',
            'role' => 'required',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'surname' => $request->surname,
            'whatsapp_number' => $request->whatsapp_number,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return response()->json($user, 201);
    }

    // Update user details
    public function update(Request $request, User $user)
    {
        $user->update($request->only(['first_name', 'last_name', 'surname', 'whatsapp_number', 'role']));
        return response()->json($user);
    }

    // Delete a user
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
