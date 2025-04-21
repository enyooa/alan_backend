<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionAssignmentController extends Controller
{
    public function catalog()
    {
        return Permission::select('id','name','code')->orderBy('id')->get();
    }
    // GET /api/users/{id}/permissions
    public function index(User $user)
    {
        // вернём только коды – так фронту проще
        return $user->permissions()->pluck('code');
    }

    // POST /api/users/{id}/permissions   body: { "codes":[1002,1004] }
    public function store(Request $request, User $user)
    {
        $codes = $request->input('codes',[]);
        $perms = Permission::whereIn('code',$codes)->pluck('id');

        $user->permissions()->syncWithoutDetaching($perms);

        return response()->json(['message'=>'разрешении присвоены']);
    }

    // DELETE /api/users/{id}/permissions/{code}
    public function destroy(User $user, $code)
    {
        $perm = Permission::where('code',$code)->firstOrFail();
        $user->permissions()->detach($perm->id);

        return response()->json(['message'=>'разрешении убраны']);
    }

    public function update(Request $request, User $user)
    {
        $codes = $request->input('codes', []);            // [1002,1005,…]

        // найдём id этих permissions
        $permissionIds = Permission::whereIn('code', $codes)->pluck('id')->toArray();

        // sync() = удалить лишние, добавить недостающие
        $user->permissions()->sync($permissionIds);

        return response()->json(['message' => 'успешно обновлен!'], 200);
    }
}
