<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\User;
use App\Models\Admin\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $rolesAllowed = ['super_admin'];
            if (!in_array(Auth::user()->role, $rolesAllowed)) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $users = User::all();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.users.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'username' => ['required', 'unique:users', 'regex:/^[A-Za-z0-9._-]+$/'],
                'password' => 'required|min:6',
                'role' => 'required|in:super_admin,admin_logistic,admin_mascot,admin_costume,admin_finance,admin_animatronic,admin_procurement,admin_hr,admin,general',
                'department_id' => 'required|exists:departments,id',
            ],
            [
                'username.regex' => 'Username can only contain letters, numbers, dots, underscores, and dashes.',
                'username.unique' => 'Username has already been taken.',
                'password.min' => 'Password must be at least 6 characters.',
            ],
        );

        $user = User::create([
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'],
        ]);

        // Cache password hash untuk tracking perubahan
        $user->cachePasswordHash();

        return redirect()
            ->route('users.index')
            ->with('success', 'User with username ' . $validated['username'] . ' created');
    }

    public function show($id)
    {
        //
    }

    public function edit(User $user)
    {
        $departments = Department::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'username' => 'required|unique:users,username,' . $id,
            'role' => 'required|in:super_admin,admin_logistic,admin_mascot,admin_costume,admin_finance,admin_animatronic,admin_procurement,admin_hr,admin,general',
            'password' => 'nullable|min:6',
            'department_id' => 'required|exists:departments,id',
        ]);

        $user = User::findOrFail($id);
        $oldUsername = $user->username;
        $passwordChanged = false;

        $user->username = $request->username;
        $user->role = $request->role;
        $user->department_id = $request->department_id;

        // Update password hanya jika diisi
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
            $passwordChanged = true;
        }

        $user->save();

        // Jika password diubah, invalidate user sessions
        if ($passwordChanged) {
            $user->invalidateSessions();

            // Jika user yang login adalah user yang diubah passwordnya, logout
            if (Auth::id() === $user->id) {
                Auth::logout();
                Session::flush();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('success', 'Your password has been updated. Please login again with your new password.');
            }

            return redirect()
                ->route('users.index')
                ->with('success', 'User with username ' . $oldUsername . ' updated successfully. Their session has been invalidated.');
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User with username ' . $user->username . ' updated successfully.');
    }

    public function destroy(User $user)
    {
        // Hindari menghapus super admin atau user aktif sendiri
        if (Auth::id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        // Invalidate sessions saat user dihapus
        $user->invalidateSessions();
        $username = $user->username;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User with username ' . $username . ' deleted successfully.');
    }
}
