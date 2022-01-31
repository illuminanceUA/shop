<?php

namespace core\base\model;

use core\base\controller\Singleton;

class Crypt
{

    use Singleton;

    private $cryptMethod = 'AES-128-CBC';
    private $hashAlgoritm = 'sha256';
    private $hashLength = 32;

    public function encrypt($str)
    {
        $ivlen = openssl_cipher_iv_length($this->cryptMethod);

        $iv = openssl_random_pseudo_bytes($ivlen);

        $cipherText = openssl_encrypt($str, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);

        $hmac = hash_hmac($this->hashAlgoritm, $cipherText, CRYPT_KEY, true);

        return base64_encode($iv.$hmac.$cipherText);

    }

    public function decrypt($str)
    {
        $cryptStr = base64_decode($str);

        $ivlen = openssl_cipher_iv_length($this->cryptMethod);

        $iv = substr($cryptStr, 0, $ivlen);

        $hmac = substr($cryptStr, $ivlen, $this->hashLength);

        $cipherText = substr($cryptStr, $ivlen + $this->hashLength);

        $originalPlaintext = openssl_decrypt($cipherText, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);

        $calcmac = hash_hmac($this->hashAlgoritm, $cipherText, CRYPT_KEY, true);

        if(hash_equals($hmac, $calcmac)) return $originalPlaintext;

        return false;

    }

}