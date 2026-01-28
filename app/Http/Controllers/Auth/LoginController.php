<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
    public function username()
    {
        return 'username';
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        $usernameValid = $user !== null;
        $passwordValid = $user && Hash::check($request->password, $user->password);

        if ($usernameValid && $passwordValid) {
            Auth::login($user, $request->filled('remember'));
            return redirect()->intended($this->redirectTo);
        }

        // Siapkan pesan error spesifik
        $errors = [];
        if (!$usernameValid) {
            $errors['username'] = 'Username not found.';
        }
        if ($usernameValid && !$passwordValid) {
            $errors['password'] = 'Password is incorrect.';
        }
        if (!$usernameValid && !$passwordValid) {
            $errors['username'] = 'Username not found.';
            $errors['password'] = 'Password is incorrect.';
        }

        return back()
            ->withErrors($errors)
            ->withInput($request->only('username', 'remember'));
    }
}
