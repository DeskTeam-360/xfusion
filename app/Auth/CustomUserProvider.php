<?php

namespace App\Auth;

use Hautelook\Phpass\PasswordHash;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class CustomUserProvider extends EloquentUserProvider
{
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];
        $hashed = $user->getAuthPassword();

        if (str_starts_with($hashed, '$wp$')) {
            $hashed = str_replace('$wp$', '$', $hashed);
            return password_verify($plain, $hashed);
        }

        if (str_starts_with($hashed, '$P$')) {
            return $this->checkPhpPass($plain, $hashed);
        }

        return parent::validateCredentials($user, $credentials);
    }

    protected function checkPhpPass($plain, $hashed)
    {
        $hasher = new PasswordHash(8, true);
        return $hasher->CheckPassword($plain, $hashed);
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
