<?php

namespace SocialDept\AtpClient\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use SocialDept\AtpClient\Enums\RefreshFailureReason;
use SocialDept\AtpClient\Exceptions\AuthenticationException;
use Throwable;

/**
 * Turn a failed token-endpoint response (or a thrown exception) into a
 * structured {@see RefreshFailureReason}, replacing the old string-sniffing.
 *
 * Parses the OAuth `error` field, handles the atproto account-gone shape
 * (`InvalidRequest` + "could not find user info"), and maps legacy XRPC error
 * names (ExpiredToken/InvalidToken/AccountTakedown) so both refresh paths agree.
 */
class OAuthErrorClassifier
{
    public function classifyResponse(Response $response): RefreshFailureReason
    {
        $status = $response->status();

        if ($status === 0) {
            return RefreshFailureReason::Network;
        }
        if ($status === 429) {
            return RefreshFailureReason::RateLimited;
        }
        if ($status >= 500) {
            return RefreshFailureReason::ServerError;
        }

        $body = $response->body();

        // HTML from a proxy/CDN instead of the API is a transient infra error.
        if (str_contains($body, '<!DOCTYPE') || str_contains($body, '<html')) {
            return RefreshFailureReason::ServerError;
        }

        return $this->classifyBody($body);
    }

    public function classifyThrowable(Throwable $e): RefreshFailureReason
    {
        // Prefer a reason already attached upstream (e.g. by TokenRefresher).
        if ($e instanceof AuthenticationException && $e->reason instanceof RefreshFailureReason) {
            return $e->reason;
        }

        if ($e instanceof ConnectionException) {
            return RefreshFailureReason::Network;
        }

        return $this->classifyMessage($e->getMessage());
    }

    private function classifyBody(string $body): RefreshFailureReason
    {
        $data = json_decode($body, true);
        $error = is_array($data) ? (string) ($data['error'] ?? '') : '';
        $message = is_array($data)
            ? (string) ($data['message'] ?? $data['error_description'] ?? '')
            : $body;

        return $this->classifyErrorCode($error, $message, $body);
    }

    private function classifyMessage(string $message): RefreshFailureReason
    {
        // Exceptions carry the raw body in their message, so reuse the same logic.
        return $this->classifyErrorCode('', $message, $message);
    }

    private function classifyErrorCode(string $error, string $message, string $rawBody): RefreshFailureReason
    {
        $haystack = strtolower($rawBody.' '.$message);

        // Account deleted — atproto returns InvalidRequest + this message.
        if (str_contains($haystack, 'could not find user') || str_contains($haystack, 'account not found')) {
            return RefreshFailureReason::AccountNotFound;
        }

        $byCode = match (strtolower($error)) {
            // OAuth terminal
            'invalid_grant' => RefreshFailureReason::InvalidGrant,
            'invalid_client' => RefreshFailureReason::InvalidClient,
            // Legacy XRPC terminal error names (shared refresh throw path)
            'expiredtoken', 'invalidtoken', 'revokedtoken' => RefreshFailureReason::InvalidGrant,
            'accounttakedown', 'account_takedown', 'accountdeactivated', 'accountsuspended' => RefreshFailureReason::AccountInactive,
            // OAuth transient
            'use_dpop_nonce' => RefreshFailureReason::UseDpopNonce,
            'temporarily_unavailable' => RefreshFailureReason::TemporarilyUnavailable,
            'slow_down' => RefreshFailureReason::SlowDown,
            default => null,
        };

        if ($byCode !== null) {
            return $byCode;
        }

        // Fallback: the structured `error` was absent or unmapped. This happens when
        // classifying an exception message that embeds the raw body. Scan for a known
        // code before giving up as Unknown (transient). Bare invalid_request stays Unknown.
        return $this->scanForKnownCode($haystack);
    }

    private function scanForKnownCode(string $haystack): RefreshFailureReason
    {
        return match (true) {
            str_contains($haystack, 'invalid_grant'),
            str_contains($haystack, 'expiredtoken'),
            str_contains($haystack, 'invalidtoken'),
            str_contains($haystack, 'revokedtoken') => RefreshFailureReason::InvalidGrant,
            str_contains($haystack, 'invalid_client') => RefreshFailureReason::InvalidClient,
            str_contains($haystack, 'accounttakedown'),
            str_contains($haystack, 'accountdeactivated'),
            str_contains($haystack, 'accountsuspended') => RefreshFailureReason::AccountInactive,
            str_contains($haystack, 'use_dpop_nonce') => RefreshFailureReason::UseDpopNonce,
            str_contains($haystack, 'temporarily_unavailable') => RefreshFailureReason::TemporarilyUnavailable,
            str_contains($haystack, 'slow_down') => RefreshFailureReason::SlowDown,
            default => RefreshFailureReason::Unknown,
        };
    }
}
