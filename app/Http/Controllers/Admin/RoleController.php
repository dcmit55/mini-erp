<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Permissions;
use App\Models\Admin\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!$user || (!$user->isSuperAdmin() && !$user->can('admin.users.edit'))) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $allPermissions = Permission::orderBy('name')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0];
        });
        return view('admin.roles.create', compact('allPermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:roles,name'],
        ], [
            'name.regex'  => 'Role name hanya boleh berisi huruf kecil, angka, dan underscore.',
            'name.unique' => 'Role name sudah digunakan.',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));

        return redirect()->route('admin.roles.index')
            ->with('success', "Role <strong>{$role->name}</strong> berhasil dibuat.");
    }

    public function edit(Role $role)
    {
        $allPermissions = Permission::orderBy('name')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0]; // group by module prefix
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'allPermissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $permissions = $request->input('permissions', []);
        $role->syncPermissions($permissions);

        return redirect()->route('admin.roles.index')
            ->with('success', "Permissions untuk role <strong>{$role->name}</strong> berhasil diperbarui.");
    }
}
