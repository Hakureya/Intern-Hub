<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Models\Role;
use App\Models\Schedule;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    protected function redirectTo()
    {
        return '/home';
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string', 'max:500'],
            'birth_date' => ['required', 'date'],
            'institution' => ['required', 'string', 'max:255'],
            'major' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
                'confirmed',
            ],
        ], [
            'password.regex' => 'Kata sandi minimal 8 karakter dan harus mengandung huruf besar, huruf kecil, angka, dan simbol.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'address' => $data['address'],
            'birth_date' => $data['birth_date'],
            'institution' => $data['institution'],
            'major' => $data['major'],
            'password' => Hash::make($data['password']),
        ]);

        // Registrasi baru otomatis mendapatkan peran pengguna (non-admin).
        $role = Role::firstOrCreate(
            ['slug' => 'user'],
            ['name' => 'Pengguna']
        );
        $user->roles()->syncWithoutDetaching([$role->id]);

        $defaultSchedule = Schedule::where('time_in', Schedule::timeInShort())
            ->where('time_out', Schedule::timeOutShort())
            ->first();
        if (!$defaultSchedule) {
            $defaultSchedule = Schedule::first();
        }
        if (!$defaultSchedule) {
            $defaultSchedule = new Schedule();
            $defaultSchedule->slug = Schedule::defaultSlug();
            $defaultSchedule->time_in = Schedule::timeInShort();
            $defaultSchedule->time_out = Schedule::timeOutShort();
            $defaultSchedule->save();
        }
        $user->schedules()->sync([$defaultSchedule->id]);

        return $user;
    }
}
