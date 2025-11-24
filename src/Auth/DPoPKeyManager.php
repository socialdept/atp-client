<?php

namespace SocialDept\AtpClient\Auth;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use SocialDept\AtpClient\Contracts\KeyStore;
use SocialDept\AtpClient\Data\DPoPKey;

class DPoPKeyManager
{
    protected AlgorithmManager $algorithmManager;

    protected JWSBuilder $jwsBuilder;

    public function __construct(
        protected KeyStore $keyStore
    ) {
        $this->algorithmManager = new AlgorithmManager([new ES256()]);
        $this->jwsBuilder = new JWSBuilder($this->algorithmManager);
    }

    /**
     * Generate new ES256 key pair
     */
    public function generateKey(string $sessionId): DPoPKey
    {
        // Generate P-256 elliptic curve key pair
        $privateKey = JWKFactory::createECKey('P-256', [
            'use' => 'sig',
            'alg' => 'ES256',
        ]);

        $publicKey = $privateKey->toPublic();
        $keyId = $this->generateKeyId($publicKey);

        $dpopKey = new DPoPKey($privateKey, $publicKey, $keyId);

        // Store the key
        $this->keyStore->store($sessionId, $dpopKey);

        return $dpopKey;
    }

    /**
     * Create DPoP proof JWT
     */
    public function createProof(
        DPoPKey $key,
        string $method,
        string $url,
        string $nonce,
        ?string $accessToken = null
    ): string {
        $now = time();

        $payload = [
            'jti' => bin2hex(random_bytes(16)),
            'htm' => $method,
            'htu' => $url,
            'iat' => $now,
            'exp' => $now + 60, // 1 minute validity
            'nonce' => $nonce,
        ];

        if ($accessToken) {
            $payload['ath'] = $this->hashAccessToken($accessToken);
        }

        $header = [
            'typ' => 'dpop+jwt',
            'alg' => 'ES256',
            'jwk' => $key->getPublicJwk(),
        ];

        $jws = $this->jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($key->privateKey, $header)
            ->build();

        $serializer = new CompactSerializer();

        return $serializer->serialize($jws, 0);
    }

    /**
     * Hash access token for DPoP proof
     */
    protected function hashAccessToken(string $token): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $token, true)), '+/', '-_'), '=');
    }

    /**
     * Generate key ID from public key
     */
    protected function generateKeyId(JWK $publicKey): string
    {
        return hash('sha256', json_encode($publicKey->jsonSerialize()));
    }
}
