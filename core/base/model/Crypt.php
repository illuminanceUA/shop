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

        return $this->cryptCombine($cipherText, $iv, $hmac);

    }

    public function decrypt($str)
    {

        $ivlen = openssl_cipher_iv_length($this->cryptMethod);

        $cryptData = $this->cryptUnCombine($str, $ivlen);

        $originalPlaintext = openssl_decrypt($cryptData['str'], $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $cryptData['iv']);

        $calcmac = hash_hmac($this->hashAlgoritm, $cryptData['str'], CRYPT_KEY, true);

        if(hash_equals($cryptData['hmac'], $calcmac)) return $originalPlaintext;

        return false;

    }

    protected function cryptCombine($str, $iv, $hmac)
    {
        $newStr = '';

        $strLen = strlen($str);

        $counter = (int) ceil(strlen(CRYPT_KEY) / ($strLen + $this->hashLength));

        $progress = 1;

        if($counter >= $strLen) $counter = 1;

        for ($i = 0; $i < $strLen; $i++){

            if($counter < $strLen){

                if($counter === $i){

                    $newStr .= substr($iv, $progress - 1, 1);
                    $progress++;
                    $counter += $progress;

                }

            }else{
                break;
            }

            $newStr .= substr($str, $i, 1);

        }

        $newStr .= substr($str, $i);
        $newStr .= substr($iv, $progress - 1);

        $newStrHalf = (int) ceil(strlen($newStr) / 2);

        $newStr = substr($newStr, 0, $newStrHalf) . $hmac .  substr($newStr, $newStrHalf);

        return base64_encode($newStr);

    }

    protected function cryptUnCombine($str, $ivlen)
    {

        $cryptData = [];

        $str = base64_decode($str);

        $hashPosition = (int) ceil(strlen($str) / 2 - $this->hashLength / 2);

        $cryptData['hmac'] = substr($str, $hashPosition, $this->hashLength);

        $str = str_replace($cryptData['hmac'], '', $str);

        $counter = (int) ceil(strlen(CRYPT_KEY) / (strlen($str) - $ivlen + $this->hashLength));

        $progress = 2;

        $cryptData['str'] = '';
        $cryptData['iv'] = '';

        for ($i = 0; $i < strlen($str); $i++){

            if($ivlen + strlen($cryptData['str']) < strlen($str)){

                if($i === $counter){

                    $cryptData['iv'] .= substr($str, $counter, 1);
                    $progress++;
                    $counter += $progress;

                }else{

                    $cryptData['str'] .= substr($str, $i, 1);

                }

            }else{

                $cryptDataLen = strlen($cryptData['str']);

                $cryptData['str'] .= substr($str, $i, strlen($str) - $ivlen - $cryptDataLen);
                $cryptData['iv'] .= substr($str, $i + (strlen($str) - $ivlen - $cryptDataLen));

                break;

            }

        }

        return $cryptData;

    }

}