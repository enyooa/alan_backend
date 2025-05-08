<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
    $users = User::with([
        'roles:id,name',             // только нужные поля
        'permissions:id,name,code'   //   — // —
    ])->get();

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
        /* ─── 1. Валидация ─── */
        $validated = $request->validate([
            'first_name'            => 'required|string',
            'last_name'             => 'nullable|string',
            'surname'               => 'nullable|string',
            'whatsapp_number'       => 'required|unique:users',
            'role'                  => 'required|in:admin,client,cashbox,packer,storager,courier',
            'password'              => 'required|string|min:6',

            /* склад (для storager / packer / courier) */
            'warehouse_name'        => 'nullable|string',
            'existing_warehouse_id' => 'nullable|uuid|exists:warehouses,id',
        ]);

        $orgId = $request->user()->organization_id;        // организация текущего админа

        /* ─── 2. Транзакция ─── */
        $user = DB::transaction(function () use ($validated, $orgId) {

            /* 2-A  Создаём пользователя */
            $user = User::create([
                'first_name'      => $validated['first_name'],
                'last_name'       => $validated['last_name']  ?? '',
                'surname'         => $validated['surname']    ?? '',
                'whatsapp_number' => $validated['whatsapp_number'],
                'password'        => Hash::make($validated['password']),
                'organization_id' => $orgId,
            ]);

            /* 2-B  Роль */
            $role = Role::where('name', $validated['role'])->first();
            $user->roles()->attach($role);

            /* 2-C  Логика со складами */
            switch ($validated['role']) {

                /* ----------  storager  ---------- */
                case 'storager':

                    // 1) Создать новый склад
                    if (!empty($validated['warehouse_name'])) {

                        // проверка на дубль имени в той же организации
                        $nameExists = Warehouse::where('organization_id', $orgId)
                                               ->where('name', $validated['warehouse_name'])
                                               ->exists();
                        if ($nameExists) {
                            throw ValidationException::withMessages([
                                'warehouse_name' => ['Склад с таким именем уже существует'],
                            ]);
                        }

                        Warehouse::create([
                            'name'            => $validated['warehouse_name'],
                            'manager_id'      => $user->id,
                            'organization_id' => $orgId,
                        ]);
                    }

                    // 2) Или назначить существующий
                    elseif (!empty($validated['existing_warehouse_id'])) {
                        $wh = Warehouse::where('id', $validated['existing_warehouse_id'])
                                       ->where('organization_id', $orgId)
                                       ->firstOrFail();

                        $wh->manager_id = $user->id;
                        $wh->save();
                    }

                    break;

                /* ----------  packer  ---------- */
                case 'packer':
                    if (!empty($validated['existing_warehouse_id'])) {
                        $wh = Warehouse::where('id', $validated['existing_warehouse_id'])
                                       ->where('organization_id', $orgId)
                                       ->firstOrFail();

                        $wh->packer_id = $user->id;
                        $wh->save();
                    }
                    break;

                /* ----------  courier  ---------- */
                case 'courier':
                    if (!empty($validated['existing_warehouse_id'])) {
                        $wh = Warehouse::where('id', $validated['existing_warehouse_id'])
                                       ->where('organization_id', $orgId)
                                       ->firstOrFail();

                        $wh->courier_id = $user->id;
                        $wh->save();
                    }
                    break;

                default:
                    // для admin / client / cashbox — ничего со складами не делаем
                    break;
            }

            return $user;
        });

        /* ─── 3. Ответ ─── */
        return response()->json([
            'message' => 'Пользователь успешно создан',
            'user'    => $user->load('roles'),
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

    // public function toggleNotifications(Request $request)
    // {
    //     $user = Auth::user();

    //     // Ensure request contains 'notifications' field
    //     if ($request->has('notifications')) {
    //         $user->notifications = $request->notifications;
    //         $user->save();
    //         return response()->json(['success' => true, 'message' => 'Notifications updated']);
    //     }

    //     return response()->json(['error' => 'Invalid request'], 400);
    // }


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

    public function stuff()
{
    /* ---------- users without any role ---------- */
    $noRoleUsers = User::doesntHave('roles')
        ->with('permissions')                    // eager‑load perms
        ->get()
        ->map(fn ($u) => $this->userPayload($u));

    /* ---------- every role with its users ---------- */
    $roles = Role::with(['users.permissions'])   // eager‑load roles→users→perms
                 ->orderBy('name')
                 ->get();

    $data = collect();

    /* block ➊  “Без ролей” */
    $data->push([
        'role'  => 'Без ролей',
        'users' => $noRoleUsers,
    ]);

    /* blocks ➋  each actual role */
    foreach ($roles as $role) {
        $data->push([
            'role'  => $role->name,
            'users' => $role->users
                           ->map(fn ($u) => $this->userPayload($u)),
        ]);
    }

    return response()->json($data->values(), 200);
}

/** Convert a user to JSON shape */
private function userPayload(User $u): array
{
    $roleNames = $u->roles->pluck('name')->values();     // ['admin', 'client', …]

    return [
        'id'          => $u->id,
        'first_name'  => $u->first_name,
        'last_name'   => $u->last_name,
        'surname'     => $u->surname,
        'whatsapp'    => $u->whatsapp_number,
        'roles'       => $roleNames,                     // ← NEW array of roles
        'permissions' => $u->permissions
                           ->map(fn ($p) => [$p->name, (string)$p->code])
                           ->values(),
    ];
}

    /* helper: что именно отдаём о пользователе  */
    public function updateStuff(Request $r, User $user)
    {
        $data = $r->validate([
            'roles'        => ['sometimes','array'],
            'roles.*'      => ['string','exists:roles,name'],
            'permissions'  => ['sometimes','array'],
            'permissions.*'=> ['integer','exists:permissions,code'],
        ]);

        if (array_key_exists('roles',$data)) {
            $roleIds = Role::whereIn('name',$data['roles'])->pluck('id');
            $user->roles()->sync($roleIds);
        }

        if (array_key_exists('permissions',$data)) {
            $permIds = Permission::whereIn('code',$data['permissions'])->pluck('id');
            $user->permissions()->sync($permIds);
        }

        return response()->json([
            'success'=>true,
            'user'   => $this->userPayload($user->fresh(['roles','permissions']))
        ]);
    }
}
