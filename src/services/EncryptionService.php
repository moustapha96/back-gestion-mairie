<?php


namespace App\services;


use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class EncryptionService
{
    private $key;

    public function __construct(string $keyPath)
    {
        if (file_exists($keyPath)) {
            $this->key = Key::loadFromAsciiSafeString(file_get_contents($keyPath));
        } else {
            $this->key = Key::createNewRandomKey();
            file_put_contents($keyPath, $this->key->saveToAsciiSafeString());
        }
    }


    public function encrypt(string $data): array
    {
        $key = Key::createNewRandomKey();
        $encryptedData = Crypto::encrypt($data, $key);
        return [
            'encryptedData' => $encryptedData,
            'key' => $key->saveToAsciiSafeString(),
        ];
    }

    public function decrypt(string $encryptedData, string $key): string
    {
        $key = Key::loadFromAsciiSafeString($key);
        return Crypto::decrypt($encryptedData, $key);
    }
}
