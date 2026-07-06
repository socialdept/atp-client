<?php

namespace SocialDept\AtpClient\Tests\Auth;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\AtpClientServiceProvider;
use SocialDept\AtpClient\Auth\OAuthErrorClassifier;
use SocialDept\AtpClient\Enums\RefreshFailureReason;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use SocialDept\AtpClient\Exceptions\OAuthSessionInvalidException;

class OAuthErrorClassifierTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AtpClientServiceProvider::class];
    }

    private function classify(int $status, string $body): RefreshFailureReason
    {
        return (new OAuthErrorClassifier())->classifyResponse(
            new Response(new PsrResponse($status, [], $body))
        );
    }

    public function test_invalid_grant_is_terminal(): void
    {
        $reason = $this->classify(400, json_encode([
            'error' => 'invalid_grant',
            'error_description' => 'Invalid refresh token',
        ]));

        $this->assertSame(RefreshFailureReason::InvalidGrant, $reason);
        $this->assertTrue($reason->isTerminal());
    }

    public function test_account_not_found_is_terminal(): void
    {
        // The atproto account-deleted shape (e.g. an ephemeral pds.rip account).
        $reason = $this->classify(400, json_encode([
            'error' => 'InvalidRequest',
            'message' => 'Could not find user info for account: did:plc:x',
        ]));

        $this->assertSame(RefreshFailureReason::AccountNotFound, $reason);
        $this->assertTrue($reason->isTerminal());
    }

    public function test_use_dpop_nonce_is_transient(): void
    {
        $reason = $this->classify(400, json_encode(['error' => 'use_dpop_nonce']));

        $this->assertSame(RefreshFailureReason::UseDpopNonce, $reason);
        $this->assertTrue($reason->isTransient());
    }

    public function test_rate_limited(): void
    {
        $this->assertSame(RefreshFailureReason::RateLimited, $this->classify(429, 'Too Many Requests'));
    }

    public function test_server_error_5xx(): void
    {
        $this->assertSame(RefreshFailureReason::ServerError, $this->classify(521, 'error code: 521'));
    }

    public function test_html_body_is_server_error(): void
    {
        $this->assertSame(RefreshFailureReason::ServerError, $this->classify(404, '<!DOCTYPE html><html></html>'));
    }

    public function test_unmapped_4xx_is_unknown_transient(): void
    {
        // A bare invalid_request (no account-gone semantics) must NOT be flagged.
        $reason = $this->classify(400, json_encode([
            'error' => 'invalid_request',
            'error_description' => 'malformed request',
        ]));

        $this->assertSame(RefreshFailureReason::Unknown, $reason);
        $this->assertTrue($reason->isTransient());
    }

    public function test_legacy_expiredtoken_is_terminal(): void
    {
        $this->assertSame(RefreshFailureReason::InvalidGrant, $this->classify(400, json_encode([
            'error' => 'ExpiredToken',
            'message' => 'Token has expired',
        ])));
    }

    public function test_account_takedown_is_inactive(): void
    {
        $this->assertSame(RefreshFailureReason::AccountInactive, $this->classify(400, json_encode([
            'error' => 'AccountTakedown',
            'message' => 'Account has been taken down',
        ])));
    }

    public function test_classify_throwable_prefers_attached_reason(): void
    {
        $e = (new AuthenticationException('boom'))->withReason(RefreshFailureReason::InvalidGrant);

        $this->assertSame(RefreshFailureReason::InvalidGrant, (new OAuthErrorClassifier())->classifyThrowable($e));
    }

    public function test_classify_throwable_connection_exception_is_network(): void
    {
        $this->assertSame(
            RefreshFailureReason::Network,
            (new OAuthErrorClassifier())->classifyThrowable(new ConnectionException('cURL error 6: Could not resolve host'))
        );
    }

    public function test_missing_refresh_token_carries_terminal_reason(): void
    {
        $e = OAuthSessionInvalidException::missingRefreshToken();

        $this->assertSame(RefreshFailureReason::MissingRefreshToken, $e->reason);
        $this->assertTrue($e->reason->isTerminal());
        $this->assertSame('missing', $e->reason->legacyReason());
    }

    public function test_legacy_reason_mapping_is_backwards_compatible(): void
    {
        $this->assertSame('invalid', RefreshFailureReason::InvalidGrant->legacyReason());
        $this->assertSame('invalid', RefreshFailureReason::AccountNotFound->legacyReason());
        $this->assertSame('auth_failed', RefreshFailureReason::InvalidClient->legacyReason());
        $this->assertSame('missing', RefreshFailureReason::MissingRefreshToken->legacyReason());
        $this->assertSame('transient', RefreshFailureReason::ServerError->legacyReason());
        $this->assertSame('transient', RefreshFailureReason::Unknown->legacyReason());
    }
}
