<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Get all users (only accessible by admin)
    public function index()
{
    // $users = User::with('roles')
    //     ->whereDoesntHave('roles', function ($query) {
    //         $query->where('name', 'admin');
    //     })
    //     ->get();
    $users = User::with('roles')->get();

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
    $validated = $request->validate([
        'first_name' => 'required|string',
        'whatsapp_number' => 'required|unique:users',
        'role'       => 'required|string',
        'password'   => 'required|string|min:6',

        // Опционально, если нужно:
        'warehouse_name'        => 'nullable|string',
        'existing_warehouse_id' => 'nullable|integer|exists:warehouses,id'
    ]);

    // 1. Создаём пользователя
    $user = User::create([
        'first_name'      => $validated['first_name'],
        'last_name'       => $request->last_name,
        'surname'         => $request->surname,
        'whatsapp_number' => $validated['whatsapp_number'],
        'password'        => Hash::make($validated['password']),
    ]);

    // 2. Присваиваем роль
    $role = Role::where('name', $validated['role'])->first();
    $user->roles()->attach($role);

    // 3. Если пользователь – Кладовщик (storager)
    if ($validated['role'] === 'storager') {
        // Если создаём новый склад
        if (!empty($validated['warehouse_name'])) {
            Warehouse::create([
                'name'       => $validated['warehouse_name'],
                'manager_id' => $user->id,
            ]);
        }
        // Или назначаем на существующий
        elseif (!empty($validated['existing_warehouse_id'])) {
            $wh = Warehouse::find($validated['existing_warehouse_id']);
            $wh->manager_id = $user->id;
            $wh->save();
        }
    }
    // 4) Упаковщик (packer) -> packer_id
    elseif ($validated['role'] === 'packer' && !empty($validated['existing_warehouse_id'])) {
        $wh = Warehouse::find($validated['existing_warehouse_id']);
        // Вместо pivot, просто записываем packer_id
        $wh->packer_id = $user->id;
        $wh->save();
    }
    // 5) Курьер (courier) -> courier_id
    elseif ($validated['role'] === 'courier' && !empty($validated['existing_warehouse_id'])) {
        $wh = Warehouse::find($validated['existing_warehouse_id']);
        // Вместо pivot, просто записываем courier_id
        $wh->courier_id = $user->id;
        $wh->save();
    }

    return response()->json([
        'message' => 'User created successfully',
        'user'    => $user->load('roles')
    ]);
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


    public function getAdminsAndStoragers()
    {
        // Предположим, role_id=1 => admin, role_id=5 => storager
        // Или вы ориентируетесь на названия ролей (name='admin'/'storager') – смотрите свою базу.

        // 1) Если в pivot хранится role_id = 1 (admin) или 5 (storager), то:
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('role_id', [1, 5]);  // Если в pivot таблице role_user => role_id
        })->get();

        // 2) Возвращаем JSON
        return response()->json($users, 200);
    }
}
