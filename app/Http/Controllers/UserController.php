<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Plan;
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
use Illuminate\Http\JsonResponse;

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
        Log::info($request);
        /* ─── 1. Валидация ─── */
        $validated = $request->validate([
            'first_name'            => 'required|string',
            'last_name'             => 'nullable|string',
            'surname'               => 'nullable|string',
        'whatsapp_number'       => 'required|unique:users,whatsapp_number',
            'role'                  => 'required|in:admin,client,cashbox,packer,storager,courier',
            'password'              => 'required|string|min:6',

            /* склад (для storager / packer / courier) */
            'warehouse_name'        => 'nullable|string',
            'existing_warehouse_id' => 'nullable|uuid|exists:warehouses,id',
        ],
        [
        /* ключ строится как: "<поле>.<правило>" */
        'whatsapp_number.required' => 'Поле «WhatsApp‑номер» обязательно.',
        'whatsapp_number.unique'   => 'Пользователь с таким WhatsApp‑номером уже существует.',
    ]


    );

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

public function stuff(): JsonResponse
{
    $rels = [
        'roles:id,name',
        'permissions:id,code,name',
        'roles.permissions:id,code,name',
        'organization.activePlans.permissions:id,code,name',
    ];

    // ------ группа «Без ролей»
    $noRoleUsers = User::doesntHave('roles')
        ->with($rels)->get()
        ->map(fn ($u) => $this->payload($u));

    $noRolePerms = $noRoleUsers->flatMap(fn ($u) => $u['permissions'])
                               ->unique('id')->values();

    $groups = collect([[
        'role'        => 'Без ролей',
        'users'       => $noRoleUsers,
        'permissions' => $noRolePerms,
    ]]);

    // ------ реальные роли
    $roles = Role::with(['users' => fn ($q) => $q->with($rels)])
                 ->orderBy('name')->get();

    foreach ($roles as $r) {
        $groups->push([
            'role'  => $r->name,
            'users' => $r->users->map(fn ($u) => $this->payload($u)),
        ]);
    }

    return response()->json($groups->values(), 200);
}

/** единичный пользователь в формате, который ждёт Vue */
private function payload(User $u): array
{
    return [
        'id'         => $u->id,
        'first_name' => $u->first_name,
        'last_name'  => $u->last_name,
        'surname'    => $u->surname,
        'whatsapp'   => $u->whatsapp_number,
        'roles'      => $u->roles->pluck('name')->values()->all(),
        'permissions'=> $u->allPermissions()
                         ->map(fn ($p)=>['id'=>$p->id,'code'=>$p->code,'name'=>$p->name])
                         ->values()->all(),
    ];
}




    /* helper: что именно отдаём о пользователе  */
 public function updateStuff(Request $r, User $user)
{
    // старый контракт: roles + permissions (полный список, что ДОЛЖЕН быть)
    $data = $r->validate([
        'roles'       => ['sometimes','array'],
        'roles.*'     => ['string','exists:roles,name'],
        'permissions' => ['sometimes','array'],
        'permissions.*'=>['integer','exists:permissions,code'],
    ]);

    DB::transaction(function () use ($data,$user) {

        /* --- роли --- */
        if (isset($data['roles'])) {
            $ids = Role::whereIn('name',$data['roles'])->pluck('id');
            $user->roles()->sync($ids);
        }

        /* --- overrides:  GRANT everything that is in the array,
                           DENY everything that is NOT in the array,
                           relative to план+роли                         */

        if (array_key_exists('permissions', $data)) {
            $want = collect($data['permissions'])->unique();

            // текущий full-set без overrides
            $base = $user->roles->flatMap->permissions
                     ->merge(
                         $user->organization && $user->organization->active_plan
                             ? $user->organization->active_plan->permissions
                             : collect()
                     )
                     ->pluck('code')
                     ->unique();

            // what to grant / deny
            $grant = $want->diff($base);
            $deny  = $base->diff($want);

            $pivot = [];

            if ($grant->isNotEmpty()) {
                $ids = Permission::whereIn('code',$grant)->pluck('id');
                foreach ($ids as $id) $pivot[$id] = ['allowed'=>true];
            }
            if ($deny->isNotEmpty()) {
                $ids = Permission::whereIn('code',$deny)->pluck('id');
                foreach ($ids as $id) $pivot[$id] = ['allowed'=>false];
            }

            // sync overrides
            $user->permissions()->sync($pivot);
        }
    });

    return response()->json(['success'=>true]);
}





    public function plans()   // ← нет JsonResponse
    {
        $plans = Plan::with('permissions:id,code,name')
                     ->orderBy('price')
                     ->get()
                     ->map(fn ($plan) => [
                         'id'          => $plan->id,
                         'name'        => $plan->name,
                         'slug'        => $plan->slug,
                         'price'       => $plan->price,
                         'period_days' => $plan->period_days,
                         'user_limit'  => $plan->user_limit,
                         'permissions' => $plan->permissions->map(fn ($p) => [
                             'id'   => $p->id,
                             'code' => $p->code,
                             'name' => $p->name,
                         ]),
                     ]);

        return $plans;
    }

    // GET /api/me
    public function me(Request $request)
{
    $u = $request->user();

    return [
        'id'          => $u->id,
        'name'        => $u->first_name,
        'roles'       => $u->roles->pluck('name'),
        'permissions' => $u->allPermissionCodes(),  // см. ниже
        'plan'        => optional($u->organization->activePlan())->slug,
        'org'         => optional($u->organization)->name,
    ];
}


}
