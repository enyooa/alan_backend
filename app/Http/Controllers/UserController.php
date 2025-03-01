<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Get all users (only accessible by admin)
    public function index()
{
    $users = User::with('roles')
        ->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'admin');
        })
        ->get();

    return response()->json($users);
}

    // админ присвоет роль
    public function assignRoles(Request $request, User $user)
{
    $request->validate([
        'role' => 'string|exists:roles,name',
    ]);

    $role = Role::where('name', $request->role)->first();
    if (!$role) {
        return response()->json(['message' => 'Role not found'], 404);
    }

    // This will add the role only if it's not already attached.
    $user->roles()->syncWithoutDetaching($role->id);

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
    public function storeUser(Request $request)
    {
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'whatsapp_number' => 'required|string|unique:users|max:15',
            'role' => 'required|string', // Allowed roles
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'surname' => $request->surname,
            'whatsapp_number' => $request->whatsapp_number,
            'password' => Hash::make($request->password),
        ]);

        // Find the role and attach it
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->attach($role->id);
        } else {
            return response()->json(['error' => 'Invalid role'], 400);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles') // Load roles in response
        ], 201);
    }

    // Update user details
    public function update(Request $request, User $user)
    {
        $user->update($request->only(['first_name', 'last_name', 'surname', 'whatsapp_number', 'role']));
        return response()->json($user);
    }

    // Delete a user
    public function deleteUser($id)
    {
        Log::info($id);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        }

        $user->roles()->detach();
        $user->delete();

        return response()->json(['message' => '✅ Сотрудник удален']);
    }

    public function getUser()
    {
        return response()->json(Auth::user());
    }

    public function toggleNotifications(Request $request)
    {
        $user = Auth::user();
        
        // Ensure request contains 'notifications' field
        if ($request->has('notifications')) {
            $user->notifications = $request->notifications;
            $user->save();
            return response()->json(['success' => true, 'message' => 'Notifications updated']);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }
}
