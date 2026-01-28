<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin\User;
use App\Models\Admin\Department;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;
    protected $redirectTo = '/dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        $departments = Department::orderBy('name')->get();
        return view('auth.register', compact('departments'));
    }

    protected function validator(array $data)
    {
        return Validator::make(
            $data,
            [
                'username' => ['required', 'string', 'max:255', 'unique:users', 'regex:/^[A-Za-z0-9._-]+$/'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role' => ['required', 'string', 'in:super_admin,admin_logistic,admin_mascot,admin_costume,admin_finance,admin_animatronic,general'],
                'department_id' => ['required', 'exists:departments,id'],
            ],
            [
                'username.regex' => 'The username may only contain letters, numbers, dots, underscores, and hyphens.',
            ],
        );
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\Admin\User
     */
    protected function create(array $data)
    {
        return User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'general', // use selected role or default to general
            'department_id' => $data['department_id'],
        ]);
    }
}
