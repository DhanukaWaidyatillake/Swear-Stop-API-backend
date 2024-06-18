<?php

namespace App\Services;

use App\Models\ApiToken;

class TokenManagerService
{
    function encrypt($data): string
    {
        $key = config('app.key');
        $iv = substr(hash('sha256', 'fixed_iv'), 0, openssl_cipher_iv_length(config('app.encryption_algorithm')));
        $encrypted = openssl_encrypt($data, config('app.encryption_algorithm'), $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    function decrypt($data): false|string
    {
        $key = config('app.key');
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length(config('app.encryption_algorithm'));
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, config('app.encryption_algorithm'), $key, 0, $iv);
    }

}
