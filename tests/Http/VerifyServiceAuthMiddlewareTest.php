<?php

namespace SocialDept\AtpClient\Tests\Http;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Math\BigInteger;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\ServiceAuth;
use SocialDept\AtpClient\Data\ServiceAuthToken;
use SocialDept\AtpClient\Http\Middleware\VerifyServiceAuthMiddleware;
use SocialDept\AtpSupport\Crypto\Base58;
use SocialDept\AtpSupport\Crypto\DidKey;
use SocialDept\AtpSupport\Crypto\SignatureVerifier;
use SocialDept\AtpSupport\Data\DidDocument;
use SocialDept\AtpSupport\Resolver;

class VerifyServiceAuthMiddlewareTest extends TestCase
{
    protected const CALLER = 'did:plc:caller';
    protected const AUDIENCE = 'did:web:example.com#forum';
    protected const METHOD = 'com.atproto.simplespace.checkUserAccess';
    protected const ORDER = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';

    protected PrivateKey $key;

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = EC::createKey('secp256k1');

        config()->set('atp-client.service_auth.audience', self::AUDIENCE);

        // Publish this test's key as the caller's #atproto key.
        $resolver = $this->createStub(Resolver::class);
        $resolver->method('resolveDid')->willReturn(DidDocument::fromArray([
            'id' => self::CALLER,
            'verificationMethod' => [[
                'id' => self::CALLER.'#atproto',
                'publicKeyMultibase' => substr($this->didKey(), strlen('did:key:')),
            ]],
        ]));

        $this->app->instance(ServiceAuth::class, new ServiceAuth($resolver, new SignatureVerifier()));

        Route::middleware('atp.service-auth:'.self::METHOD)->get('/xrpc/'.self::METHOD, function () {
            $token = request()->attributes->get(VerifyServiceAuthMiddleware::ATTRIBUTE);

            return response()->json(['caller' => $token->did(), 'method' => $token->method]);
        });
    }

    public function test_a_valid_token_reaches_the_handler(): void
    {
        $this->bearing($this->mint())
            ->getJson('/xrpc/'.self::METHOD)
            ->assertOk()
            ->assertJson(['caller' => self::CALLER, 'method' => self::METHOD]);
    }

    /**
     * The handler must never run for an unauthenticated request, and the
     * refusal should look like an XRPC error.
     */
    public function test_a_missing_token_is_refused(): void
    {
        $this->getJson('/xrpc/'.self::METHOD)
            ->assertStatus(401)
            ->assertJson(['error' => 'AuthMissing']);
    }

    public function test_a_token_for_another_method_is_refused(): void
    {
        $this->bearing($this->mint(method: 'com.atproto.repo.deleteRecord'))
            ->getJson('/xrpc/'.self::METHOD)
            ->assertStatus(401)
            ->assertJson(['error' => 'BadJwtLexiconMethod']);
    }

    public function test_a_token_for_another_audience_is_refused(): void
    {
        $this->bearing($this->mint(audience: 'did:web:elsewhere.example'))
            ->getJson('/xrpc/'.self::METHOD)
            ->assertStatus(401)
            ->assertJson(['error' => 'BadJwtAudience']);
    }

    public function test_an_expired_token_is_refused(): void
    {
        $this->bearing($this->mint(lifetime: -300))
            ->getJson('/xrpc/'.self::METHOD)
            ->assertStatus(401)
            ->assertJson(['error' => 'JwtExpired']);
    }

    public function test_a_token_signed_by_another_key_is_refused(): void
    {
        $jwt = $this->app->make(ServiceAuth::class)->mint(
            self::CALLER,
            self::AUDIENCE,
            self::METHOD,
            $this->signerFor(EC::createKey('secp256k1')),
        );

        $this->bearing($jwt)
            ->getJson('/xrpc/'.self::METHOD)
            ->assertStatus(401)
            ->assertJson(['error' => 'BadJwtSignature']);
    }

    /**
     * A DPoP-scheme header is a different auth mechanism entirely and must not
     * be mistaken for a service auth token.
     */
    public function test_a_dpop_header_is_not_accepted(): void
    {
        $this->withHeader('Authorization', 'DPoP '.$this->mint())
            ->getJson('/xrpc/'.self::METHOD)
            ->assertStatus(401)
            ->assertJson(['error' => 'AuthMissing']);
    }

    public function test_the_verified_token_is_available_to_the_handler(): void
    {
        $response = $this->bearing($this->mint())->getJson('/xrpc/'.self::METHOD);

        $this->assertInstanceOf(
            ServiceAuthToken::class,
            $response->baseRequest->attributes->get(VerifyServiceAuthMiddleware::ATTRIBUTE) ?? null,
        );
    }

    protected function bearing(string $jwt): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$jwt);
    }

    protected function mint(
        string $audience = self::AUDIENCE,
        ?string $method = self::METHOD,
        int $lifetime = 60,
    ): string {
        return $this->app->make(ServiceAuth::class)->mint(
            self::CALLER,
            $audience,
            $method,
            $this->signerFor($this->key),
            'ES256K',
            $lifetime,
        );
    }

    /**
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

    protected function didKey(): string
    {
        $point = $this->key->getPublicKey()->getEncodedCoordinates();
        $x = substr($point, 1, 32);
        $y = substr($point, 33, 32);

        return 'did:key:z'.Base58::encode(DidKey::SECP256K1_PREFIX.chr(0x02 | (ord($y[31]) & 1)).$x);
    }
}
