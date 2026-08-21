<?php

namespace SocialDept\AtpClient\Tests\Unit\Auth;

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpClient\Auth\ServiceAuth;
use SocialDept\AtpClient\Exceptions\ServiceAuthException;
use SocialDept\AtpSupport\Crypto\Base58;
use SocialDept\AtpSupport\Crypto\DidKey;
use SocialDept\AtpSupport\Crypto\SignatureVerifier;
use SocialDept\AtpSupport\Data\DidDocument;
use SocialDept\AtpSupport\Resolver;

class ServiceAuthTest extends TestCase
{
    protected const CALLER = 'did:plc:caller';
    protected const AUDIENCE = 'did:web:example.com#forum';
    protected const METHOD = 'com.atproto.simplespace.checkUserAccess';

    protected const ORDER = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';

    protected PrivateKey $key;

    protected function setUp(): void
    {
        $this->key = EC::createKey('secp256k1');
    }

    public function test_it_verifies_a_token_from_a_known_service(): void
    {
        $auth = $this->serviceAuth();
        $jwt = $this->mint($auth);

        $token = $auth->verify($jwt, self::AUDIENCE, self::METHOD);

        $this->assertSame(self::CALLER, $token->did());
        $this->assertSame(self::AUDIENCE, $token->audience);
        $this->assertSame(self::METHOD, $token->method);
        $this->assertTrue($token->authorizes(self::METHOD));
        $this->assertGreaterThan(0, $token->secondsRemaining());
    }

    /**
     * The point of `lxm`: a token minted to call one endpoint must not be
     * spendable at another.
     */
    public function test_it_rejects_a_token_minted_for_another_method(): void
    {
        $auth = $this->serviceAuth();
        $jwt = $this->mint($auth, method: 'com.atproto.repo.deleteRecord');

        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage('was not issued for');

        $auth->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    public function test_it_rejects_a_token_addressed_to_another_service(): void
    {
        $auth = $this->serviceAuth();
        $jwt = $this->mint($auth, audience: 'did:web:somewhere-else.example');

        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage('addressed to another service');

        $auth->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        $auth = $this->serviceAuth();
        $jwt = $this->mint($auth, lifetime: -120);

        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage('expired');

        $auth->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    /**
     * The signature must be checked against the key the issuer publishes, not
     * merely be well-formed.
     */
    public function test_it_rejects_a_token_signed_by_another_key(): void
    {
        // The DID document publishes our key; the token is signed with another.
        $auth = $this->serviceAuth();

        $jwt = $auth->mint(
            self::CALLER,
            self::AUDIENCE,
            self::METHOD,
            $this->signerFor(EC::createKey('secp256k1')),
        );

        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage('signature is invalid');

        $auth->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    public function test_it_rejects_a_tampered_payload(): void
    {
        $auth = $this->serviceAuth();
        [$header, $payload, $signature] = explode('.', $this->mint($auth));

        $forged = rtrim(strtr(base64_encode((string) json_encode([
            'iss' => 'did:plc:someone-else',
            'aud' => self::AUDIENCE,
            'lxm' => self::METHOD,
            'exp' => time() + 60,
        ])), '+/', '-_'), '=');

        $this->expectException(ServiceAuthException::class);

        $auth->verify($header.'.'.$forged.'.'.$signature, self::AUDIENCE, self::METHOD);
    }

    /**
     * "none" must never reach the signature check.
     */
    public function test_it_rejects_an_unsigned_token(): void
    {
        $auth = $this->serviceAuth();

        $encode = fn (array $data) => rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
        $jwt = $encode(['typ' => 'JWT', 'alg' => 'none'])
            .'.'.$encode(['iss' => self::CALLER, 'aud' => self::AUDIENCE, 'exp' => time() + 60])
            .'.';

        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage('unsupported "alg"');

        $auth->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedProvider(): array
    {
        return [
            'not a jwt' => ['nonsense', 'expected 3 parts'],
            'two parts' => ['a.b', 'expected 3 parts'],
            'unparseable' => ['!!!.!!!.!!!', 'not JSON'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedProvider')]
    public function test_it_rejects_malformed_tokens(string $jwt, string $expected): void
    {
        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage($expected);

        $this->serviceAuth()->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    public function test_it_rejects_a_non_did_issuer(): void
    {
        $auth = $this->serviceAuth();
        $jwt = $this->mint($auth, issuer: 'example.com');

        $this->expectException(ServiceAuthException::class);
        $this->expectExceptionMessage('"iss" is not a DID');

        $auth->verify($jwt, self::AUDIENCE, self::METHOD);
    }

    /**
     * A token with no `lxm` is not method-bound, so a route asking for one
     * still accepts it — the binding is the issuer's choice to narrow.
     */
    public function test_an_unbound_token_authorizes_any_method(): void
    {
        $auth = $this->serviceAuth();
        $token = $auth->verify($this->mint($auth, method: null), self::AUDIENCE, self::METHOD);

        $this->assertNull($token->method);
        $this->assertTrue($token->authorizes('com.example.anything'));
    }

    public function test_it_reads_a_bearer_header(): void
    {
        $auth = $this->serviceAuth();

        $this->assertSame('abc.def.ghi', $auth->fromHeader('Bearer abc.def.ghi'));
        $this->assertSame('abc.def.ghi', $auth->fromHeader('bearer abc.def.ghi'));
        $this->assertNull($auth->fromHeader('DPoP abc.def.ghi'));
        $this->assertNull($auth->fromHeader(''));
        $this->assertNull($auth->fromHeader(null));
    }

    public function test_the_audience_check_is_skipped_when_unset(): void
    {
        $auth = $this->serviceAuth();
        $jwt = $this->mint($auth, audience: 'did:web:anything.example');

        $this->assertSame('did:web:anything.example', $auth->verify($jwt)->audience);
    }

    /**
     * A ServiceAuth whose resolver publishes this test's key.
     */
    protected function serviceAuth(): ServiceAuth
    {
        return new ServiceAuth(
            $this->resolverFor($this->didKeyFor($this->key)),
            new SignatureVerifier(),
        );
    }

    protected function resolverFor(string $didKey): Resolver
    {
        $document = DidDocument::fromArray([
            'id' => self::CALLER,
            'verificationMethod' => [[
                'id' => self::CALLER.'#atproto',
                'publicKeyMultibase' => substr($didKey, strlen('did:key:')),
            ]],
        ]);

        $resolver = $this->createStub(Resolver::class);
        $resolver->method('resolveDid')->willReturn($document);

        return $resolver;
    }

    protected function mint(
        ServiceAuth $auth,
        string $issuer = self::CALLER,
        string $audience = self::AUDIENCE,
        ?string $method = self::METHOD,
        int $lifetime = 60,
    ): string {
        return $auth->mint($issuer, $audience, $method, $this->signer(), 'ES256K', $lifetime);
    }

    /**
     * @return callable(string): string
     */
    protected function signer(): callable
    {
        return $this->signerFor($this->key);
    }

    /**
     * A low-S normalizing signer for a given key.
     *
     * The normalization is not optional: phpseclib emits high-S roughly half
     * the time, and a conforming verifier rejects those.
     *
     * @return callable(string): string
     */
    protected function signerFor(PrivateKey $key): callable
    {
        $order = new BigInteger(self::ORDER, 16);

        return function (string $data) use ($key, $order): string {
            $signature = $key->withSignatureFormat('Raw')->withHash('sha256')->sign($data);
            [$half] = $order->divide(new BigInteger(2));
            $s = $signature['s']->compare($half) > 0 ? $order->subtract($signature['s']) : $signature['s'];

            return str_pad($signature['r']->toBytes(), 32, "\0", STR_PAD_LEFT)
                .str_pad($s->toBytes(), 32, "\0", STR_PAD_LEFT);
        };
    }

    protected function didKeyFor(PrivateKey $key): string
    {
        $point = $key->getPublicKey()->getEncodedCoordinates();
        $x = substr($point, 1, 32);
        $y = substr($point, 33, 32);

        return 'did:key:z'.Base58::encode(DidKey::SECP256K1_PREFIX.chr(0x02 | (ord($y[31]) & 1)).$x);
    }
}
