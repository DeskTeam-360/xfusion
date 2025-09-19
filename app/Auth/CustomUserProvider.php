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
        $rawHashed = $user->getAuthPassword();

        // Handle WordPress-style hashes with $wp$ prefix
        if (str_starts_with($rawHashed, '$wp$')) {
            $fixedHash = str_replace('$wp$', '$', trim($rawHashed));
            return password_verify($plain, $fixedHash);
        }

        // Handle WordPress PHPass hashes with $P$ prefix
        if (str_starts_with($rawHashed, '$P$')) {
            return $this->checkPhpPass($plain, $rawHashed);
        }

        // Handle standard Laravel Bcrypt hashes
        if (str_starts_with($rawHashed, '$2y$') || str_starts_with($rawHashed, '$2a$') || str_starts_with($rawHashed, '$2b$')) {
            return password_verify($plain, $rawHashed);
        }

        // Fallback to parent method for other hash types
        return parent::validateCredentials($user, $credentials);
    }

    protected function checkPhpPass($plain, $hashed)
    {
        $hasher = new PasswordHash(8, true);
        return $hasher->CheckPassword($plain, $hashed);
    }

    public function retrieveByCredentials(array $credentials)
    {
        // Map Laravel's 'email' field to WordPress 'user_email' field
        if (isset($credentials['email'])) {
            $credentials['user_email'] = $credentials['email'];
            unset($credentials['email']);
        }
        
        // Remove password from query credentials
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
