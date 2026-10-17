<?php

namespace SocialDept\AtpClient\Tests\Client;

use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Data\Responses\Atproto\Server\GetServiceAuthResponse;
use SocialDept\AtpClient\Enums\Nsid\AtprotoRepo;
use SocialDept\AtpClient\Enums\Nsid\AtprotoServer;
use SocialDept\AtpClient\Facades\Atp;

class GetServiceAuthTest extends TestCase
{
    protected const AUDIENCE = 'did:web:video.example#atproto_video';

    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    public function test_it_requests_a_method_bound_token_for_the_audience(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::GetServiceAuth, ['token' => $this->token(['lxm' => 'com.atproto.repo.uploadBlob'])]);

        $exp = time() + 300;
        $response = Atp::as('did:plc:alice')->atproto->server->getServiceAuth(self::AUDIENCE, 'com.atproto.repo.uploadBlob', $exp);

        $this->assertInstanceOf(GetServiceAuthResponse::class, $response);
        $fake->assertCalledAuthenticated(AtprotoServer::GetServiceAuth, 'did:plc:alice');
        $fake->assertCalledWith(AtprotoServer::GetServiceAuth, fn ($request) => $request->hasParam('aud', self::AUDIENCE)
            && $request->hasParam('lxm', 'com.atproto.repo.uploadBlob')
            && $request->hasParam('exp', $exp));
    }

    public function test_it_accepts_an_enum_method(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::GetServiceAuth, ['token' => $this->token()]);

        Atp::as('did:plc:alice')->atproto->server->getServiceAuth(self::AUDIENCE, AtprotoRepo::UploadBlob);

        $fake->assertCalledWith(AtprotoServer::GetServiceAuth, fn ($request) => $request->hasParam('lxm', AtprotoRepo::UploadBlob->value));
    }

    public function test_it_omits_optional_parameters_that_are_not_given(): void
    {
        $fake = Atp::fake();
        $fake->stub(AtprotoServer::GetServiceAuth, ['token' => $this->token()]);

        Atp::as('did:plc:alice')->atproto->server->getServiceAuth(self::AUDIENCE);

        $fake->assertCalledWith(AtprotoServer::GetServiceAuth, fn ($request) => $request->hasParam('aud', self::AUDIENCE)
            && ! $request->hasParam('lxm')
            && ! $request->hasParam('exp'));
    }

    public function test_the_response_reads_the_token_claims(): void
    {
        $exp = time() + 60;
        $response = GetServiceAuthResponse::fromArray(['token' => $this->token([
            'aud' => self::AUDIENCE,
            'lxm' => 'com.atproto.repo.uploadBlob',
            'exp' => $exp,
        ])]);

        $this->assertSame($exp, $response->expiresAt());
        $this->assertSame(self::AUDIENCE, $response->audience());
        $this->assertSame('com.atproto.repo.uploadBlob', $response->method());
        $this->assertSame(['token' => $response->token], $response->toArray());
    }

    public function test_an_unbound_or_unreadable_token_reports_null_claims(): void
    {
        $unbound = GetServiceAuthResponse::fromArray(['token' => $this->token(['lxm' => null])]);
        $garbage = GetServiceAuthResponse::fromArray(['token' => 'not-a-jwt']);

        $this->assertNull($unbound->method());
        $this->assertNull($garbage->expiresAt());
        $this->assertNull($garbage->audience());
    }

    /**
     * An unsigned JWT-shaped token: the response only reads claims, it never
     * verifies them.
     *
     * @param  array<string, mixed>  $claims
     */
    protected function token(array $claims = []): string
    {
        $payload = array_filter(array_merge([
            'iss' => 'did:plc:alice',
            'aud' => self::AUDIENCE,
            'exp' => time() + 60,
        ], $claims), fn ($value) => $value !== null);

        $encode = fn (array $part) => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');

        return $encode(['typ' => 'JWT', 'alg' => 'ES256K']).'.'.$encode($payload).'.signature';
    }
}
