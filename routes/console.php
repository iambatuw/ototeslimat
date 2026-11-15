<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('guard:admin {email} {--name=Yönetici}', function (string $email) {
    $password = $this->secret('Şifre (en az 10 karakter, harf ve rakam)');

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', Password::min(10)->letters()->numbers()]],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::create(['name' => $this->option('name'), 'email' => strtolower($email), 'password' => $password]);
    $user->forceFill(['role' => User::ROLE_ADMIN])->save();

    $this->info("Yönetici oluşturuldu: {$user->email}");
})->purpose('İlk yönetici hesabını oluşturur');
