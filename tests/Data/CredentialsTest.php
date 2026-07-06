<?php

namespace SocialDept\AtpClient\Tests\Data;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpClient\Data\Credentials;

class CredentialsTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_expires_in_returns_int_seconds_remaining(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        // 3545.43s in the future — the fractional part is what tripped the PHP 8.4
        // implicit float->int deprecation before the explicit cast.
        $creds = $this->credentials(Carbon::now()->addSeconds(3545)->addMilliseconds(434));

        $this->assertIsInt($creds->expiresIn());
        $this->assertSame(3545, $creds->expiresIn());
    }

    public function test_expires_in_is_negative_once_expired(): void
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $creds = $this->credentials(Carbon::now()->subSeconds(60));

        $this->assertIsInt($creds->expiresIn());
        $this->assertSame(-60, $creds->expiresIn());
        $this->assertTrue($creds->isExpired());
    }

    private function credentials(\DateTimeInterface $expiresAt): Credentials
    {
        return new Credentials(
            did: 'did:plc:test',
            accessToken: 'a',
            refreshToken: 'r',
            expiresAt: $expiresAt,
        );
    }
}
