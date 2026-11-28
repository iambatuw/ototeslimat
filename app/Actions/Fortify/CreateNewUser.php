<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        abort_unless(\App\Models\Setting::flag('registration_open'), 404);

        if (isset($input['username'])) {
            $input['username'] = Str::lower(trim((string) $input['username']));
        }

        Validator::make($input, [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:40',
                'regex:/^[a-z0-9_.\-]+$/',
                Rule::unique(User::class, 'username'),
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => $this->passwordRules(),
        ], [
            'username.regex' => 'Kullanıcı adı sadece harf, rakam, nokta, tire ve alt çizgi içerebilir.',
        ], [
            'username' => 'kullanıcı adı',
        ])->validate();

        return User::create([
            'name' => $input['name'] ?? $input['username'],
            'username' => $input['username'],
            'email' => filled($input['email'] ?? null) ? Str::lower($input['email']) : null,
            'password' => Hash::make($input['password']),
        ]);
    }
}
