<?php

namespace SocialDept\AtpClient\Storage;

use Illuminate\Contracts\Encryption\Encrypter;
use Jose\Component\Core\JWK;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\DPoPKey;

class EncryptedFileKeyStore implements KeyStore
{
    public function __construct(
        protected string $storagePath,
        protected ?Encrypter $encrypter = null,
    ) {
        $this->encrypter = $this->encrypter ?? app('encrypter');

        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function store(string $sessionId, DPoPKey $key): void
    {
        $data = [
            'privateKey' => $key->getPrivateJwk(),
            'publicKey' => $key->getPublicJwk(),
            'keyId' => $key->keyId,
        ];

        $encrypted = $this->encrypter->encrypt($data);

        file_put_contents(
            $this->getKeyPath($sessionId),
            $encrypted
        );
    }

    public function get(string $sessionId): ?DPoPKey
    {
        $path = $this->getKeyPath($sessionId);

        if (! file_exists($path)) {
            return null;
        }

        $encrypted = file_get_contents($path);
        $data = $this->encrypter->decrypt($encrypted);

        return new DPoPKey(
            privateKey: JWK::createFromJson(json_encode($data['privateKey'])),
            publicKey: JWK::createFromJson(json_encode($data['publicKey'])),
            keyId: $data['keyId'],
        );
    }

    public function delete(string $sessionId): void
    {
        $path = $this->getKeyPath($sessionId);

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function exists(string $sessionId): bool
    {
        return file_exists($this->getKeyPath($sessionId));
    }

    protected function getKeyPath(string $sessionId): string
    {
        return $this->storagePath.'/'.hash('sha256', $sessionId).'.key';
    }
}
