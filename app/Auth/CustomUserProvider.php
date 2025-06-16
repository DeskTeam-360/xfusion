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
//        dd($user);
        $hashed = $user->getAuthPassword();

        if (str_starts_with($hashed, '$wp$')) {
            $hashed = str_replace('$wp$', '$', $hashed);
            $debugHashed = str_replace('$wp$', '$', $hashed);
            $credentials['password'] = $hashed;
            dd([
                'plain' => $plain,
                'hashed_raw' => $hashed,
                'hashed_fixed' => $debugHashed,
                'result' => password_verify($plain, $debugHashed)
            ]);

//            dd($credentials);
//            dd($plain, $hashed, password_verify($plain, $hashed));
//            return password_verify($plain, $hashed);
//            return parent::validateCredentials($user, $credentials);
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
//        dd($credentials);
        $credentials['user_email'] = $credentials['email'];
        unset($credentials['email']);
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
