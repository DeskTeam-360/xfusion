<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];
        $hashed = $user->getAuthPassword();

        if (str_starts_with($hashed, '$wp$')) {
            $hashed = str_replace('$wp$', '$', $hashed);
            return password_verify($plain, $hashed);
        }

        if (str_starts_with($hashed, '$P$')) {
            // Butuh library PHPass kalau kamu ingin dukung hash lama
            return $this->checkPhpPass($plain, $hashed);
        }

        return parent::validateCredentials($user, $credentials);
    }

    protected function checkPhpPass($password, $hash)
    {
        // kamu bisa pakai library ini via composer:
        // composer require hautelook/phpass

        $hasher = new \Hautelook\Phpass\PasswordHash(8, true);
        return $hasher->CheckPassword($password, $hash);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $queryCredentials = array_filter(
            $credentials,
            fn($key) => $key !== 'password',
            ARRAY_FILTER_USE_KEY
        );

        $query = $this->createModel()->newQuery();

        foreach ($queryCredentials as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }
}
